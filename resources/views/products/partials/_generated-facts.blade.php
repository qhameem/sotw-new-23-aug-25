@if($product->summary)
                    <section class="mt-5" aria-label="Product summary">
                        <p class="text-sm leading-6 text-gray-700">{{ $product->summary }}</p>
                        @php
                            $factLabels = ['price_amount' => 'Price', 'pricing_model' => 'Pricing model', 'platforms' => 'Platforms', 'minimum_os' => 'Minimum OS', 'version' => 'Version', 'last_updated' => 'Last updated', 'license' => 'License'];
                            $facts = $product->facts_json ?? [];
                        @endphp
                        <dl class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach($factLabels as $key => $label)
                                @php $value = $facts[$key] ?? null; @endphp
                                @if(filled($value))
                                    <div class="rounded-lg border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">{{ $label }}</dt>
                                        <dd class="text-sm text-gray-900">{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                        <div class="mt-3 flex gap-5 text-xs text-gray-500">
                            <span>{{ number_format((int) $product->outbound_clicks_count) }} website clicks</span>
                            @if($product->published_at)
                                <span>Launch week {{ $product->published_at->isoWeek() }}, {{ $product->published_at->isoWeekYear() }}</span>
                            @endif
                        </div>
                    </section>
                @endif

