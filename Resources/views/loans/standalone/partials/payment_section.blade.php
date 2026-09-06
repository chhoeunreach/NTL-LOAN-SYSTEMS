@php
    $loanLanguage = session('user.language', config('app.locale'));
    $lmIsKhmer = $loanLanguage === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

<div class="lm-step-card lm-step-card-emerald" id="sectionPayment">
    <div class="lm-step-card-header">
        <div class="lm-step-card-title-wrap">
            <span class="lm-step-badge">4</span>
            <div>
                <h3 class="lm-step-title"><i class="fa fa-money text-success"></i> {{ $lmText('Down Payment & Initial Settlement', 'ប្រាក់កក់ដំបូង និងការទូទាត់មុន') }}</h3>
                <p class="lm-step-subtitle">{{ $lmText('Record upfront cash/bank down payment. Setting 0 means 100% full financing.', 'កត់ត្រាប្រាក់កក់ដំបូង (Down Payment)។ បើមិនមានកក់ សូមទុក 0') }}</p>
            </div>
        </div>
        <div class="lm-step-header-actions">
            <span class="lm-badge-pill lm-badge-pill-emerald">
                <i class="fa fa-check-circle"></i> {{ $lmText('Optional Upfront', 'ស្រេចចិត្ត') }}
            </span>
        </div>
    </div>

    <div class="lm-step-card-body">
        <div class="row">
            <!-- Down Payment Amount with Quick % Shortcuts -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <div class="lm-label-with-shortcuts">
                        <label class="lm-field-label">{{ $lmText('Down Payment Amount', 'ចំនួនប្រាក់កក់ដំបូង') }}</label>
                        <div class="lm-quick-presets" id="downPaymentPercentPresets">
                            <button type="button" class="lm-preset-btn active" data-pct="0">0%</button>
                            <button type="button" class="lm-preset-btn" data-pct="10">10%</button>
                            <button type="button" class="lm-preset-btn" data-pct="20">20%</button>
                            <button type="button" class="lm-preset-btn" data-pct="30">30%</button>
                            <button type="button" class="lm-preset-btn" data-pct="50">50%</button>
                        </div>
                    </div>
                    <input type="number" step="0.01" id="payment_amount_input" name="payment[amount]" class="form-control lm-input-styled lm-input-emerald" value="0" min="0">
                    <input type="hidden" id="down_payment_hidden" name="down_payment" value="0">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Payment Received Date', 'កាលបរិច្ឆេទទទួលប្រាក់') }}</label>
                    <input type="date" name="payment[paid_date]" class="form-control lm-input-styled" value="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Payment Method / Account', 'វិធីសាស្ត្រទូទាត់') }}</label>
                    {!! Form::select('payment[method]', $paymentTypes ?? [], $defaultPaymentMethod ?? 'cash', ['class' => 'form-control lm-input-styled select2', 'style' => 'width:100%;']) !!}
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Reference / Slip Number', 'លេខយោងបង្កាន់ដៃ') }}</label>
                    <input name="payment[reference_number]" class="form-control lm-input-styled" placeholder="Ref #">
                </div>
            </div>

            <!-- Currency & Status -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Currency', 'រូបិយប័ណ្ណ') }}</label>
                    <select name="payment[currency]" class="form-control lm-input-styled">
                        <option value="USD" selected>USD ($)</option>
                        <option value="KHR">KHR (៛)</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Exchange Rate', 'អត្រាប្តូរប្រាក់') }}</label>
                    <input type="number" step="0.0001" name="payment[exchange_rate]" class="form-control lm-input-styled" value="1">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Payment Status', 'ស្ថានភាពទូទាត់') }}</label>
                    <select name="payment[status]" class="form-control lm-input-styled">
                        <option value="completed" selected>{{ $lmText('Completed (ទូទាត់រួច)', 'ទូទាត់រួច (Completed)') }}</option>
                        <option value="pending">{{ $lmText('Pending (រង់ចាំ)', 'រង់ចាំ (Pending)') }}</option>
                        <option value="failed">{{ $lmText('Failed (មិនបានជោគជ័យ)', 'មិនបានជោគជ័យ (Failed)') }}</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Received By Officer', 'អ្នកទទួលប្រាក់') }}</label>
                    <input class="form-control lm-input-styled" value="{{ trim((auth()->user()->first_name ?? '').' '.(auth()->user()->last_name ?? '')) }}" readonly>
                </div>
            </div>

            <!-- Extra Account Details -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Account Name', 'ឈ្មោះគណនី') }}</label>
                    <input name="payment[account_name]" class="form-control lm-input-styled" placeholder="ABA / ACLEDA account name">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Account Number', 'លេខគណនី') }}</label>
                    <input name="payment[account_number]" class="form-control lm-input-styled" placeholder="000 123 456">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Transaction / Bank Trx ID', 'លេខប្រតិបត្តិការធនាគារ') }}</label>
                    <input name="payment[transaction_id]" class="form-control lm-input-styled" placeholder="TRX-987654">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Payment Channel', 'បណ្តាញទូទាត់') }}</label>
                    <input name="payment[channel]" class="form-control lm-input-styled" placeholder="Cash / ABA PayWay / Bakong / Wing">
                </div>
            </div>

            <div class="col-sm-12 col-md-12">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Payment Remarks / Note', 'ចំណាំការទូទាត់') }}</label>
                    <input name="payment[note]" class="form-control lm-input-styled" placeholder="{{ $lmText('Optional note about down payment transaction...', 'កំណត់ចំណាំបន្ថែម...') }}">
                </div>
            </div>
        </div>
    </div>
</div>
