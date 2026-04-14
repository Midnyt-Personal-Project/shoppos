<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Facades\{GlobalShortcut, Menu, MenuBar, Notification, Window};
use App\Events\{SaleCompleted, StockLow};

class NativeAppServiceProvider extends ServiceProvider
{
    /**
     * Boot method — everything NativePHP needs is configured here.
     * This runs once when the desktop app starts.
     */
    public function boot(): void
    {
        // ── 1. Main Application Window ────────────────────────────────────────
        Window::open()
            ->title('OmniPOS — Point of Sale')
            ->width(1280)
            ->height(800)
            ->minWidth(960)
            ->minHeight(600)
            ->rememberState()        // remember size/position between launches
            ->titleBarHidden(false);

        // ── 2. Application Menu (top menu bar on macOS / Windows) ─────────────
        if (auth()->check()) {
            Menu::create(
                // macOS app menu (About, Services, Hide, Quit, etc.)
                Menu::app(),

                // ── File menu ──────────────────────────────────────────────────
                Menu::make(
                    Menu::route('pos.index', 'New Sale')
                        ->hotkey('CmdOrCtrl+N'),

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

                )->label('File'),

                // ── Edit menu (standard undo/redo/cut/copy/paste) ──────────────
                Menu::edit(),

                // ── View menu ──────────────────────────────────────────────────
                Menu::make(
                    Menu::route('dashboard', 'Dashboard')
                        ->hotkey('CmdOrCtrl+D'),

                    Menu::separator(),

                    Menu::route('products.index', 'Products')
                        ->hotkey('CmdOrCtrl+P'),

                    Menu::route('customers.index', 'Customers'),

                    Menu::route('expenses.index', 'Expenses'),

                    Menu::separator(),

                    Menu::route('users.index', 'Staff'),

                    Menu::route('branches.index', 'Branches'),

                    Menu::separator(),

                    Menu::fullscreen('Toggle Full Screen')
                        ->hotkey('CmdOrCtrl+Shift+F'),

                )->label('View'),

                // ── POS menu ───────────────────────────────────────────────────
                Menu::make(
                    Menu::route('pos.index', 'Open POS Screen')
                        ->hotkey('CmdOrCtrl+Shift+P'),

                    Menu::separator(),

                    Menu::label('Version 1.0.2')
                        ->disabled(),

                    Menu::separator(),

                    Menu::route('license.index', 'License & Activation'),

                )->label('OmniPOS'),

                // ── Help menu ──────────────────────────────────────────────────
                Menu::make(
                    Menu::label('OmniPOS v1.0.2'),

                    Menu::separator(),

                    Menu::link('https://license-s.oyalo.net/', 'Documentation')
                        ->openInBrowser(),

                    Menu::link('https://license-s.oyalo.net/', 'Get Support')
                        ->openInBrowser(),

                    Menu::separator(),

                    // Menu::label('Report a Bug')
                    //     ->event(\App\Events\Native\ReportBugClicked::class),

                    Menu::separator(),

                    Menu::link('https://omnipos.app/changelog', 'What\'s New in v1.0.2')
                        ->openInBrowser(),

                )->label('Help'),

                // ── Window menu (standard minimise/zoom/close) ─────────────────
                Menu::window(),
            );
        }

        // ── 3. System Tray / Menu Bar icon ────────────────────────────────────
        // This puts a small icon in the system tray (Windows) / menu bar (macOS)
        // so the app is accessible even when the main window is minimised.
        if (auth()->check()) {
            MenuBar::create()
                ->icon(public_path('images/tray-icon.png'))  // 22x22 PNG, transparent bg
                ->tooltip('OmniPOS v1.0.2')
                ->label('')                                   // no text, icon only
                ->showDockIcon()                              // keep the dock icon visible
                ->width(320)
                ->height(420)
                ->route('pos.index')
                ->withContextMenu(
                    Menu::make(
                        Menu::label('OmniPOS v1.0.2')
                            ->disabled(),

                        Menu::separator(),

                        Menu::route('pos.index', 'New Sale')
                            ->hotkey('CmdOrCtrl+N'),

                        Menu::route('dashboard', 'Dashboard'),

                        Menu::separator(),

                        Menu::route('reports.sales', 'Sales Report'),

                        Menu::route('reports.stock', 'Stock Report'),

                        Menu::separator(),

                        Menu::route('settings.index', 'Settings'),

                        Menu::route('license.index', 'License'),

                        Menu::separator(),

                        Menu::quit('Quit OmniPOS'),
                    )
                );
        }

        // ── 4. Global Hotkeys ─────────────────────────────────────────────────
        // These fire even when the app window is not focused
        // GlobalShortcut::key('CmdOrCtrl+Shift+S')
        //     ->event(\App\Events\Native\GlobalNewSale::class);

        // ── 5. Native Notifications for Events ──────────────────────────────
        Event::listen(SaleCompleted::class, function (SaleCompleted $event) {
            $sale = $event->sale->load(['branch.shop']);
            $currency = $sale->branch->shop->currency_symbol;

            Notification::create()
                ->title('New Sale Completed')
                ->body("Sale {$sale->reference} - {$currency}" . number_format($sale->total, 2) . " at {$sale->branch->name}")
                ->show();
        });

        Event::listen(StockLow::class, function (StockLow $event) {
            $branch = $event->branch;
            $itemsCount = count($event->items);

            Notification::create()
                ->title('Low Stock Alert')
                ->body("{$itemsCount} item(s) are low in stock at {$branch->name}")
                ->show();
        });
    }
}