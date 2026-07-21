<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Blade::directive('interact', function (mixed $expression): string {
            // Parsear la expresión como lo hace TallStackUI
            $directive = array_map('trim', preg_split('/,(?![^(]*[)])/', $expression));
            $directive[1] ??= ''; // Prevenir error cuando no hay segundo parámetro

            [$name, $arguments] = $directive;

            // Extraer parámetros adicionales
            $parameters = collect(array_flip($directive))
                ->except($name, $arguments)
                ->flip()
                ->push('$__env')
                ->implode(',');

            // Normalizar nombre (reemplazar puntos por guiones bajos)
            $name = 'column_'.str_replace('.', '_', trim($name, "'\""));

            // Devolver código PHP para registrar el slot
            return "<?php \$__env->slot('{$name}', function({$arguments}) use ({$parameters}) { ?>";
        });

        Blade::directive('endinteract', fn (): string => '<?php }); ?>');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
