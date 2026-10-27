<?php

namespace App\Livewire\Admin\Consultation;

use App\Models\Consulta;
use App\Models\MapaCorporal;
use App\Models\RegionCorporal;
use App\Models\ZonaDolor;
use App\Services\ConsultationService;
use App\Support\PainMapCatalog;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** Mapa de dolor interactivo: el profesional marca regiones sobre la imagen y registra su EVA por sesión. */
class PainMap extends Component
{
    public int $consultationId;

    public bool $editable = false;

    /** Vista visible del mapa: ambas, anterior o posterior. */
    public string $view = 'ambas';

    public bool $noPain = false;

    public string $observaciones = '';

    /** Zonas capturadas, indexadas por "regionId-lado". Se guardan todas juntas con save(). */
    public array $zones = [];

    /** Clave de la zona seleccionada ("regionId-lado") o null. */
    public ?string $selected = null;

    /** Datos de la zona seleccionada mientras se edita. */
    public array $zone = [];

    /** Consulta anterior elegida para comparar. */
    public ?int $compareId = null;

    // Carga el mapa guardado de la consulta, si existe, para continuar editándolo.
    //   Uso:
    //     @livewire(PainMap::class, ['consultationId' => $consultation->id, 'editable' => true])
    //   Resultado: Las zonas guardadas aparecen pintadas en el mapa.
    public function mount(int $consultationId, bool $editable = false): void
    {
        $this->consultationId = $consultationId;
        $this->editable = $editable && (bool) auth()->user()?->can('consultations.edit');

        $map = MapaCorporal::query()->with('zonasDolor')->where('consulta_id', $consultationId)->first();
        if (! $map) {
            return;
        }

        $this->noPain = $map->estado === 'sin_dolor';
        $this->observaciones = $map->observaciones ?? '';
        foreach ($map->zonasDolor as $saved) {
            $this->zones[PainMapCatalog::key($saved->region_id, $saved->lateralidad)] = $this->zoneData(
                $saved->region_id,
                $saved->lateralidad,
                [
                    'eva' => $saved->eva,
                    'tipos' => $saved->tipos ?? [],
                    'irradiacion' => $saved->irradiacion ?? [],
                    'inicio' => $saved->inicio ?? '',
                    'patron' => $saved->patron ?? [],
                    'comentario' => $saved->comentario ?? '',
                ]
            );
        }
    }

    // Regiones activas del catálogo con sus contornos, para dibujar el SVG.
    //   Uso:
    //     @foreach ($this->regions as $region) ... @endforeach
    //   Resultado: Colección de regiones ordenadas por vista y orden.
    #[Computed]
    public function regions(): Collection
    {
        return RegionCorporal::query()->where('activo', true)->whereNotNull('trazos')->orderBy('vista')->orderBy('orden')->get();
    }

    // Consulta la consulta actual para conocer el expediente del paciente.
    //   Uso:
    //     $this->consultation()->expediente_id
    //   Resultado: Modelo Consulta de este mapa.
    #[Computed]
    public function consultation(): Consulta
    {
        return Consulta::query()->findOrFail($this->consultationId);
    }

    // Consultas anteriores del mismo expediente que tienen mapa de dolor, para elegir una a comparar.
    //   Uso:
    //     @foreach ($this->previousConsultations as $c) ...
    //   Resultado: Lista de consultas con mapa, de la más reciente a la más antigua.
    #[Computed]
    public function previousConsultations(): Collection
    {
        return Consulta::query()
            ->where('expediente_id', $this->consultation->expediente_id)
            ->where('id', '!=', $this->consultationId)
            ->whereHas('mapaCorporal.zonasDolor')
            ->orderByDesc('fecha_hora')
            ->get(['id', 'fecha_hora', 'numero_consulta']);
    }

    // Zonas de la consulta elegida para comparar, indexadas por "regionId-lado" con su EVA.
    //   Uso:
    //     $this->compareZones['3-derecha'] => 6
    //   Resultado: Arreglo clave => EVA, vacío si no hay comparación.
    #[Computed]
    public function compareZones(): array
    {
        if (! $this->compareId || ! $this->previousConsultations->contains('id', $this->compareId)) {
            return [];
        }

        return ZonaDolor::query()
            ->whereHas('mapa', fn ($q) => $q->where('consulta_id', $this->compareId))
            ->get()
            ->mapWithKeys(fn (ZonaDolor $z) => [PainMapCatalog::key($z->region_id, $z->lateralidad) => $z->eva])
            ->all();
    }

    // Historial de EVA de la zona seleccionada en las sesiones guardadas del paciente, más el valor actual.
    //   Uso:
    //     @foreach ($this->history as $point) {{ $point['label'] }} {{ $point['eva'] }}
    //   Resultado: Puntos cronológicos [label, eva] para tabla y gráfico.
    #[Computed]
    public function history(): array
    {
        if (! $this->selected) {
            return [];
        }

        [$regionId, $lado] = explode('-', $this->selected);

        $points = ZonaDolor::query()
            ->join('mapas_corporales', 'mapas_corporales.id', '=', 'zonas_dolor.mapa_id')
            ->join('consultas', 'consultas.id', '=', 'mapas_corporales.consulta_id')
            ->where('consultas.expediente_id', $this->consultation->expediente_id)
            ->where('consultas.id', '!=', $this->consultationId)
            ->where('zonas_dolor.region_id', $regionId)
            ->where('zonas_dolor.lateralidad', $lado)
            ->orderBy('consultas.fecha_hora')
            ->get(['zonas_dolor.eva', 'consultas.fecha_hora'])
            ->map(fn ($row) => ['label' => Carbon::parse($row->fecha_hora)->format('d/m/Y'), 'eva' => (int) $row->eva])
            ->all();

        if (isset($this->zones[$this->selected])) {
            $points[] = ['label' => 'Hoy', 'eva' => (int) $this->zones[$this->selected]['eva']];
        }

        return $points;
    }

    // Selecciona una región (clic en el SVG o en la lista) y carga sus datos para editarlos.
    //   Uso:
    //     @click="$wire.select(3, 'derecha')"
    //   Resultado: Se abre el panel de la zona con su EVA actual o valores iniciales.
    public function select(int $regionId, string $lado): void
    {
        $region = RegionCorporal::query()->where('activo', true)->findOrFail($regionId);
        abort_unless(in_array($lado, $region->tiene_lateralidad ? ['derecha', 'izquierda'] : ['central'], true), 422);

        $key = PainMapCatalog::key($regionId, $lado);
        $this->selected = $key;
        $this->zone = $this->zones[$key] ?? $this->zoneData($regionId, $lado, ['eva' => 5]);
        $this->resetValidation();
    }

    // Valida los datos del panel y los agrega al mapa (aún sin guardar en la base de datos).
    //   Uso:
    //     wire:click="applyZone"
    //   Resultado: La región queda pintada según su EVA y aparece en la lista resumen.
    public function applyZone(): void
    {
        abort_unless($this->editable, 403);
        abort_unless($this->selected !== null, 422);

        $data = $this->validate([
            'zone.eva' => ['required', 'integer', 'min:0', 'max:10'],
            'zone.tipos' => ['array'],
            'zone.tipos.*' => ['in:'.implode(',', array_keys(PainMapCatalog::tipos()))],
            'zone.irradiacion' => ['array'],
            'zone.irradiacion.*' => ['exists:regiones_corporales,codigo'],
            'zone.inicio' => ['nullable', 'string', 'max:120'],
            'zone.patron' => ['array'],
            'zone.patron.*' => ['in:'.implode(',', array_keys(PainMapCatalog::patrones()))],
            'zone.comentario' => ['nullable', 'string', 'max:2000'],
        ])['zone'];

        [$regionId, $lado] = explode('-', $this->selected);
        $this->zones[$this->selected] = $this->zoneData((int) $regionId, $lado, $data);
        $this->noPain = false;
        $this->selected = null;
        $this->zone = [];
    }

    // Quita una zona del mapa (aún sin guardar).
    //   Uso:
    //     wire:click="removeZone('3-derecha')"
    //   Resultado: La región vuelve a gris y desaparece de la lista.
    public function removeZone(string $key): void
    {
        abort_unless($this->editable, 403);
        unset($this->zones[$key]);
        if ($this->selected === $key) {
            $this->selected = null;
            $this->zone = [];
        }
    }

    // Cierra el panel de la zona sin aplicar cambios.
    //   Uso:
    //     wire:click="cancelZone"
    //   Resultado: No hay región seleccionada.
    public function cancelZone(): void
    {
        $this->selected = null;
        $this->zone = [];
    }

    // Persiste el mapa completo mediante el servicio y notifica a la ficha para actualizar la intensidad.
    //   Uso:
    //     wire:click="save"
    //   Resultado: Zonas guardadas con auditoría; se emite 'pain-map-saved' con EVA máximo y zona principal.
    public function save(ConsultationService $service): void
    {
        abort_unless($this->editable, 403);
        $this->validate(['observaciones' => ['nullable', 'string', 'max:5000']]);

        $map = $service->savePainMap(
            $this->consultationId,
            $this->noPain,
            filled($this->observaciones) ? trim($this->observaciones) : null,
            array_values($this->zones),
        );

        $this->dispatch('pain-map-saved', eva: $map->eva_maximo, zona: $map->zona_mayor_dolor);
        Flux::toast(text: 'Mapa de dolor guardado.', variant: 'success');
    }

    // Renderiza la vista del mapa con las regiones, la lista resumen y la comparación.
    //   Uso:
    //     Livewire ejecuta render() al mostrar o actualizar el componente.
    //   Resultado: SVG, panel de zona, historial y comparación.
    public function render()
    {
        $regions = $this->regions->keyBy('id');
        $summary = collect($this->zones)
            ->map(fn (array $z, string $key) => [
                ...$z,
                'key' => $key,
                'region' => $regions->get($z['region_id'])?->nombre ?? '—',
                'previous' => $this->compareZones[$key] ?? null,
            ])
            ->sortByDesc('eva')
            ->values();

        return view('livewire.admin.consultation.pain-map', ['summary' => $summary]);
    }

    // Normaliza los datos de una zona al formato interno.
    //   Uso:
    //     $this->zoneData(3, 'derecha', ['eva' => 6])
    //   Resultado: Arreglo completo con valores por defecto.
    private function zoneData(int $regionId, string $lado, array $data): array
    {
        return [
            'region_id' => $regionId,
            'lateralidad' => $lado,
            'eva' => (int) ($data['eva'] ?? 0),
            'tipos' => array_values($data['tipos'] ?? []),
            'irradiacion' => array_values($data['irradiacion'] ?? []),
            'inicio' => (string) ($data['inicio'] ?? ''),
            'patron' => array_values($data['patron'] ?? []),
            'comentario' => (string) ($data['comentario'] ?? ''),
        ];
    }
}
