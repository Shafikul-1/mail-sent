<?php

namespace App\View\Components;

use Illuminate\View\Component;

class alertComponent extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.alert-component');
    }
}
