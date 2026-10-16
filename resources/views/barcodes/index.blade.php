@extends('layouts.app')

@section('title', __('messages.barcodes'))
@section('header', __('messages.barcodes'))

@section('content')
<div class="space-y-6" x-data="barcodeWorkstation()">

    <!-- TOP CONTROL & SCANNER CARD -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Hardware / Keyboard Scanner Input -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></div>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Hardware / Keyboard Scanner Ready</h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                    Point any USB or Bluetooth barcode scanner at a label, or type a barcode manually below:
                </p>
                <div class="relative">
                    <input type="text" x-model="scannedBarcode" @keydown.enter.prevent="performLookup(scannedBarcode)"
                           placeholder="Type barcode or scan..." 
                           class="w-full text-sm font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <button @click="performLookup(scannedBarcode)" class="absolute right-2 top-2 px-3 py-1 rounded-lg bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700">
                        Scan
                    </button>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400">
                Listening for hardware scanner keystrokes in real-time.
            </div>
        </div>

        <!-- Camera Scanner Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-2">Mobile / Tablet Camera Scanner</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                    Use your device's built-in camera to scan Code 128 barcodes directly.
                </p>
                <div id="reader" class="rounded-xl overflow-hidden mb-2 bg-slate-100 dark:bg-slate-800 text-xs text-center" :class="{ 'min-h-[160px]': cameraActive }"></div>
            </div>
            <div>
                <button type="button" @click="toggleCamera()" 
                        class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-md shadow-indigo-600/30"
                        x-text="cameraActive ? 'Stop Camera' : 'Start Camera Scanner'">
                    Start Camera Scanner
                </button>
            </div>
        </div>

        <!-- Real-time Scan Result Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-2">Scanned Product Details</h3>
                <div x-show="!scannedResult && !lookupError" class="text-xs text-slate-400 py-6 text-center">
                    No barcode scanned yet. Scan a label to inspect product and stock.
                </div>
                <div x-show="lookupError" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 text-rose-600 text-xs font-semibold" x-text="lookupError"></div>

                <div x-show="scannedResult" class="space-y-3">
                    <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-800/60">
                        <div class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400 font-bold" x-text="scannedResult?.sku"></div>
                        <div class="font-extrabold text-base text-slate-900 dark:text-white mt-0.5" x-text="scannedResult?.product_name"></div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1 flex items-center gap-2">
                            <span>Color: <strong x-text="scannedResult?.color"></strong></span>
                            <span>•</span>
                            <span>Size: <strong x-text="scannedResult?.size"></strong></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800">
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Selling Price</div>
                            <div class="text-base font-black text-indigo-600 dark:text-indigo-400" x-text="scannedResult?.selling_price_formatted"></div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800">
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Store Stock</div>
                            <div class="text-base font-black text-slate-800 dark:text-slate-200" x-text="scannedResult?.stock_on_hand + ' Units'"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div x-show="scannedResult" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <a :href="'/products/' + scannedResult?.product_id" class="text-xs font-bold text-indigo-600 hover:underline">View in Catalog →</a>
            </div>
        </div>
    </div>

    <!-- BATCH LABEL PRINTING WORKSTATION -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
        <form action="{{ route('barcodes.print') }}" method="POST" target="_blank">
            @csrf
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">Batch Barcode Label Generator</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Select product variants, configure print dimensions, and generate thermal print sheets.</p>
                </div>
                
                <!-- Label Configuration Settings -->
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Store on Label</label>
                        <select name="store_id" class="text-xs rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-2.5">
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $selectedStore?->id == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Size (WxH mm)</label>
                        <div class="flex items-center gap-1">
                            <input type="number" name="label_width" value="50" min="30" max="100" class="w-14 text-xs text-center rounded-lg border-slate-300 dark:border-slate-700 py-1.5">
                            <span class="text-xs text-slate-400">×</span>
                            <input type="number" name="label_height" value="30" min="20" max="80" class="w-14 text-xs text-center rounded-lg border-slate-300 dark:border-slate-700 py-1.5">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="py-2 px-5 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Print Selected Labels</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Variants Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                            <th class="py-3 px-3 w-10">Select</th>
                            <th class="py-3 px-3">Product Name & Category</th>
                            <th class="py-3 px-3">SKU</th>
                            <th class="py-3 px-3">Variant (Color / Size)</th>
                            <th class="py-3 px-3">Barcode (Code 128)</th>
                            <th class="py-3 px-3 text-right">Selling MRP</th>
                            <th class="py-3 px-3 w-24 text-center">Print Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @foreach($variants as $variant)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3 px-3">
                                    <input type="checkbox" name="variant_ids[]" value="{{ $variant->id }}" checked
                                           class="rounded text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                    {{ $variant->product->name }}
                                    <div class="text-[11px] font-normal text-slate-400">{{ $variant->product->category->name }}</div>
                                </td>
                                <td class="py-3 px-3 font-mono font-semibold text-slate-600 dark:text-slate-400">
                                    {{ $variant->product->sku }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-semibold">{{ $variant->color }}</span>
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-bold text-indigo-600">{{ $variant->size }}</span>
                                </td>
                                <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white tracking-wider">
                                    {{ $variant->barcode }}
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                    @bdt($variant->effective_selling_price)
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <input type="number" name="quantities[{{ $variant->id }}]" value="1" min="1" max="200"
                                           class="w-16 text-center text-xs rounded-lg border-slate-300 dark:border-slate-700 py-1 font-semibold">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $variants->links() }}
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
function barcodeWorkstation() {
    return {
        scannedBarcode: '',
        scannedResult: null,
        lookupError: '',
        cameraActive: false,
        scannerObj: null,

        init() {
            // Register hardware keyboard scanner listener
            if (window.initHardwareScanner) {
                window.initHardwareScanner((barcode) => {
                    this.scannedBarcode = barcode;
                    this.performLookup(barcode);
                });
            }
        },

        performLookup(barcode) {
            const code = barcode ? barcode.trim() : '';
            if (!code) return;

            this.lookupError = '';
            fetch('{{ route('barcodes.scan-lookup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ barcode: code })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.scannedResult = data.variant;
                    this.lookupError = '';
                } else {
                    this.scannedResult = null;
                    this.lookupError = data.message || 'Product variant not found.';
                }
            })
            .catch(() => {
                this.lookupError = 'Network or server error during barcode lookup.';
            });
        },

        toggleCamera() {
            if (this.cameraActive) {
                if (this.scannerObj) {
                    this.scannerObj.clear();
                }
                this.cameraActive = false;
            } else {
                this.cameraActive = true;
                this.$nextTick(() => {
                    if (window.Html5QrcodeScanner) {
                        this.scannerObj = new Html5QrcodeScanner("reader", { fps: 10, qrbox: { width: 250, height: 150 } });
                        this.scannerObj.render((decodedText) => {
                            this.scannedBarcode = decodedText;
                            this.performLookup(decodedText);
                        });
                    }
                });
            }
        }
    };
}
</script>
@endpush
@endsection
