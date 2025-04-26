<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register custom CSS for responsive Repeater
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => <<<'CSS'
                <style>
                    /* Desktop: Style Repeater as a table */
                    .custom-repeater .filament-forms-repeater-component {
                        display: table;
                        width: 100%;
                        border-collapse: collapse;
                    }

                    .custom-repeater .filament-forms-repeater-component > div {
                        display: table-row;
                    }

                    .custom-repeater .filament-forms-repeater-component .filament-forms-repeater-component-item {
                        display: table-row;
                        border-bottom: 1px solid #e5e7eb;
                    }

                    .custom-repeater .filament-forms-repeater-component .filament-forms-repeater-component-item > div {
                        display: table-cell;
                        padding: 0.5rem;
                        vertical-align: middle;
                    }

                    /* Header row for desktop */
                    .custom-repeater .filament-forms-repeater-component::before {
                        content: "";
                        display: table-row;
                    }

                    .commissions-repeater .filament-forms-repeater-component::before {
                        content: "Title  Amount  Actions";
                        display: table-row;
                        font-weight: bold;
                        background-color: #e5e7eb;
                    }

                    .items-repeater .filament-forms-repeater-component::before {
                        content: "Title  Operator  Amount  Actions";
                        display: table-row;
                        font-weight: bold;
                        background-color: #e5e7eb;
                    }

                    .custom-repeater .filament-forms-repeater-component::before > * {
                        display: table-cell;
                        padding: 0.5rem;
                    }

                    /* Mobile: Stack items vertically */
                    @media (max-width: 640px) {
                        .custom-repeater .filament-forms-repeater-component {
                            display: block;
                        }

                        .custom-repeater .filament-forms-repeater-component::before {
                            display: none; /* Hide headers on mobile */
                        }

                        .custom-repeater .filament-forms-repeater-component > div,
                        .custom-repeater .filament-forms-repeater-component .filament-forms-repeater-component-item {
                            display: block;
                            margin-bottom: 1rem;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.375rem;
                            padding: 0.5rem;
                        }

                        .custom-repeater .filament-forms-repeater-component .filament-forms-repeater-component-item > div {
                            display: flex;
                            flex-direction: column;
                            padding: 0.25rem 0;
                        }

                        /* Add labels for each field */
                        .commissions-repeater .filament-forms-repeater-component-item > div:nth-child(1)::before {
                            content: "Title: ";
                            font-weight: bold;
                            margin-bottom: 0.25rem;
                        }
                        .commissions-repeater .filament-forms-repeater-component-item > div:nth-child(2)::before {
                            content: "Amount: ";
                            font-weight: bold;
                            margin-bottom: 0.25rem;
                        }

                        .items-repeater .filament-forms-repeater-component-item > div:nth-child(1)::before {
                            content: "Title: ";
                            font-weight: bold;
                            margin-bottom: 0.25rem;
                        }
                        .items-repeater .filament-forms-repeater-component-item > div:nth-child(2)::before {
                            content: "Operator: ";
                            font-weight: bold;
                            margin-bottom: 0.25rem;
                        }
                        .items-repeater .filament-forms-repeater-component-item > div:nth-child(3)::before {
                            content: "Amount: ";
                            font-weight: bold;
                            margin-bottom: 0.25rem;
                        }

                        /* Hide actions label on mobile */
                        .custom-repeater .filament-forms-repeater-component-item > div:last-child::before {
                            content: none;
                        }

                        /* Ensure inputs and selects take full width */
                        .custom-repeater .filament-forms-repeater-component-item input,
                        .custom-repeater .filament-forms-repeater-component-item select {
                            width: 100%;
                        }

                        /* Style the action buttons (reorder, clone, delete) */
                        .custom-repeater .filament-forms-repeater-component-item .filament-forms-repeater-component-item-actions {
                            display: flex;
                            justify-content: flex-end;
                            gap: 0.5rem;
                            margin-top: 0.5rem;
                        }
                    }

                    /* Dark mode adjustments */
                    @media (max-width: 640px) and (prefers-color-scheme: dark) {
                        .custom-repeater .filament-forms-repeater-component-item {
                            border-color: #4b5563;
                            background-color: #1f2937;
                        }
                        .custom-repeater .filament-forms-repeater-component-item > div {
                            color: #e5e7eb;
                        }
                    }

                    /* Desktop dark mode for table header */
                    @media (prefers-color-scheme: dark) {
                        .custom-repeater .filament-forms-repeater-component::before {
                            background-color: #4b5563;
                            color: #e5e7eb;
                        }
                        .custom-repeater .filament-forms-repeater-component .filament-forms-repeater-component-item {
                            border-bottom-color: #4b5563;
                            color: #e5e7eb;
                        }
                    }
                </style>
            CSS
        );
    }
}
