<?php

namespace App\Traits;

trait Interact
{
    //=== Stepper propertys and functions ========

    public int $step = 1;

    public function handleStep($step)
    {

        if ($step >= 1 && $step <= count($this->steps)) {

            if ($step > $this->step) {
                $this->validateCurrentStep();
            }

            $this->step = $step;
        }
    }

    public function nextStep()
    {

        if ($this->step < count($this->steps)) {
            $this->validateCurrentStep();
            $this->step++;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    protected function validateCurrentStep()
    {
        //
    }

    // Navbar and interactions

    public ?int $nav_option = 1;

    public function navToggle(int $option)
    {
        $this->nav_option = $option;
    }
}
