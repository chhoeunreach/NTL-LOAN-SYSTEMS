@php
    $loanLanguage = session('user.language', config('app.locale'));
    $lmIsKhmer = $loanLanguage === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

<div class="lm-step-card lm-step-card-indigo" id="sectionItems">
    <div class="lm-step-card-header">
        <div class="lm-step-card-title-wrap">
            <span class="lm-step-badge">2</span>
            <div>
                <h3 class="lm-step-title"><i class="fa fa-cubes text-info"></i> {{ $lmText('Purchased Items & Collateral Products', 'ទំនិញបង់រំលស់ ឬទ្រព្យធានា') }}</h3>
                <p class="lm-step-subtitle">{{ $lmText('Add line items, scan/type IMEI or serial number for instant product auto-detection.', 'បញ្ចូលមុខទំនិញ បញ្ចូល IMEI ឬលេខស៊េរីដើម្បីទាញយកព័ត៌មានស្វ័យប្រវត្តិ') }}</p>
            </div>
        </div>
        <div class="lm-step-header-actions">
            <button type="button" class="btn btn-primary btn-sm lm-btn-action" id="btnAddItem">
                <i class="fa fa-plus-circle"></i> {{ $lmText('Add Item Row', 'បន្ថែមមុខទំនិញ') }}
            </button>
        </div>
    </div>

    <div class="lm-step-card-body">
        <div class="table-responsive lm-table-responsive-clean">
            <table class="table table-bordered lm-items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width:28%;">{{ $lmText('Product / Item Name', 'ឈ្មោះទំនិញ / ម៉ូដែល') }} <span class="text-danger">*</span></th>
                        <th style="width:14%;">{{ $lmText('SKU / Code', 'កូដទំនិញ') }}</th>
                        <th style="width:18%;">{{ $lmText('IMEI / Serial Number', 'លេខ IMEI / ស៊េរី') }}</th>
                        <th style="width:12%; text-align:center;">{{ $lmText('Photo', 'រូបភាព') }}</th>
                        <th style="width:10%; text-align:center;">{{ $lmText('Qty', 'ចំនួន') }}</th>
                        <th style="width:14%; text-align:right;">{{ $lmText('Unit Price', 'តម្លៃឯកតា') }}</th>
                        <th style="width:14%; text-align:right;">{{ $lmText('Line Total', 'សរុប') }}</th>
                        <th style="width:5%; text-align:center;"></th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="lm-table-total-row">
                        <td colspan="6" class="text-right lm-total-label">
                            <strong><i class="fa fa-calculator text-primary"></i> {{ $lmText('Total Product Price (Principal Base):', 'តម្លៃទំនិញសរុប (ប្រាក់ដើម):') }}</strong>
                        </td>
                        <td class="text-right lm-total-amount">
                            <span id="computedPrincipal" class="lm-badge-total">0.00</span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="lm-item-hint-strip">
            <i class="fa fa-lightbulb-o text-warning"></i>
            <span>{{ $lmText('Smart IMEI lookup will automatically identify products from POS stock & inventory when typing 3+ characters.', 'ប្រព័ន្ធនឹងទាញយកឈ្មោះទំនិញស្វ័យប្រវត្តិនៅពេលលោកអ្នកបញ្ចូល IMEI ឬ Serial ចាប់ពី ៣ តួ') }}</span>
        </div>
    </div>
</div>
