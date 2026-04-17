<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Events\ManualUpdateCheck;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Events\AutoUpdater\{CheckingForUpdate, Error, UpdateAvailable, UpdateDownloaded, UpdateNotAvailable};
use Native\Desktop\Facades\{AutoUpdater, Menu, Window};

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
       
    AutoUpdater::checkForUpdates();
    AutoUpdater::quitAndInstall();
    // ─── Register AutoUpdater Event Listeners FIRST ─────────────
        // \Illuminate\Support\Facades\Event::listen(
        //     CheckingForUpdate::class,
        //     function () {
        //         \Illuminate\Support\Facades\Log::info('Checking for updates...');
        //     }
        // );

        // \Illuminate\Support\Facades\Event::listen(
        //     UpdateAvailable::class,
        //     function ($event) {
        //         \Illuminate\Support\Facades\Log::info('Update available: ' . $event->version);
        //     }
        // );

        // \Illuminate\Support\Facades\Event::listen(
        //     UpdateNotAvailable::class,
        //     function () {
        //         \Illuminate\Support\Facades\Log::info('No update available');
        //     }
        // );

        // \Illuminate\Support\Facades\Event::listen(
        //     UpdateDownloaded::class,
        //     function ($event) {
        //         \Illuminate\Support\Facades\Log::info('Update downloaded: ' . $event->version);
        //     }
        // );

        // \Illuminate\Support\Facades\Event::listen(
        //     Error::class,
        //     function ($event) {
        //         \Illuminate\Support\Facades\Log::error('Update error: ' . $event->error);
        //     }
        // );

        // // ─── Register Manual Update Check Event ────────────────────
        // \Illuminate\Support\Facades\Event::listen(
        //     ManualUpdateCheck::class,
        //     function () {
        //         \Illuminate\Support\Facades\Log::info('Manual update check triggered');
        //         AutoUpdater::checkForUpdates();
        //     }
        // );
    $menus = [];

        // ─── File Menu ─────────────────────────────────────────────
        $menus[] = Menu::make(
            Menu::route('pos.index', 'New Sale')
                ->hotkey('CmdOrCtrl+N'),

            // Optional: dynamic role label (you can update this via JS later)
            

            Menu::separator(),

            Menu::route('sales.index', 'Sales History')
                ->hotkey('CmdOrCtrl+H'),

            Menu::route('purchase-orders.index', 'Purchase Orders'),

            Menu::separator(),

            Menu::route('reports.sales', 'Sales Report')
                ->hotkey('CmdOrCtrl+R'),

            Menu::route('reports.stock', 'Stock Report'),

            Menu::separator(),

            Menu::route('settings.index', 'Settings')
                ->hotkey('CmdOrCtrl+,'),

            Menu::separator(),

            Menu::quit('Quit OmniPOS')
                ->hotkey('CmdOrCtrl+Q'),
        )->label('File');

        // // ─── Edit Menu (standard shortcuts) ─────────────────────────
        $menus[] = Menu::make(
            // These can be linked to frontend events or left as disabled
            Menu::link('#', 'Undo')->hotkey('CmdOrCtrl+Z'),
            Menu::link('#', 'Redo')->hotkey('CmdOrCtrl+Shift+Z'),
            Menu::separator(),
            Menu::link('#', 'Cut')->hotkey('CmdOrCtrl+X'),
            Menu::link('#', 'Copy')->hotkey('CmdOrCtrl+C'),
            Menu::link('#', 'Paste')->hotkey('CmdOrCtrl+V'),
            Menu::separator(),
            Menu::link('#', 'Select All')->hotkey('CmdOrCtrl+A'),
        )->label('Edit');

        // // ─── System Menu (version & license info) ───────────────────
        $menus[] = Menu::make(
            // Menu::route('about', 'About OmniPOS')
            //     ->hotkey('CmdOrCtrl+I'),

            Menu::link('https://license-s.oyalo.net/buy', 'License / Activation Code')->hotkey('CmdOrCtrl+Shift+B'),
            Menu::route('documentation', 'View Documentation')
                ->hotkey('CmdOrCtrl+Shift+D'),

            Menu::separator(),

       


        )->label('System');

  

    

        Menu::create(...$menus);

        Window::open()
            ->title('OmniPOS — Point of Sale')
            ->width(1280)
            ->height(800)
            ->minWidth(960)
            ->minHeight(600)
            ->rememberState();
    }

    public function phpIni(): array
    {
        return [];
    }
}