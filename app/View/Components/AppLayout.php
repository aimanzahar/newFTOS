<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $user = Auth::user();
        $role = $user ? (int)$user->role : 0;

        // Map roles to your new folder structure
        $layout = match($role) {
            6       => 'layouts.admin.admin-layout',
            2       => 'layouts.ftadmin.ftadmin-layout',
            3       => 'layouts.ftworker.ftworker-layout',
            1       => 'layouts.customer.customer-layout',
            default => 'layouts.app', 
        };

        return view($layout);
    }
}