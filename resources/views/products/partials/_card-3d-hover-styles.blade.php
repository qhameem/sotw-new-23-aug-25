@once
    @push('styles')
        <style>
            .product-alternatives-panel,
            .product-3d-hover-card {
                transition:
                    transform 160ms cubic-bezier(0.2, 0.8, 0.2, 1),
                    border-color 160ms ease,
                    box-shadow 160ms cubic-bezier(0.2, 0.8, 0.2, 1),
                    background-color 160ms ease;
            }

            @media (hover: hover) and (pointer: fine) {
                .product-alternatives-panel:has(.product-3d-hover-card:hover) {
                    transform: translate(-2px, -2px);
                    border-color: #111827;
                    box-shadow: 7px 7px 0 var(--color-primary-500, #0091ff);
                }

                .product-3d-hover-card:hover {
                    transform: translate(-2px, -2px);
                    border-color: #111827;
                    background-color: #fff;
                    box-shadow: 6px 6px 0 var(--color-primary-500, #0091ff);
                }
            }

            .product-3d-hover-card:focus-visible {
                outline: 2px solid var(--color-primary-500, #0091ff);
                outline-offset: 3px;
            }

            @media (prefers-reduced-motion: reduce) {
                .product-alternatives-panel,
                .product-3d-hover-card {
                    transition: none;
                }
            }
        </style>
    @endpush
@endonce
