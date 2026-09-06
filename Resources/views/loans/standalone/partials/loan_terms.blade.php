@php
    $loanLanguage = session('user.language', config('app.locale'));
    $lmIsKhmer = $loanLanguage === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

<div class="lm-step-card lm-step-card-amber" id="sectionTerms">
    <div class="lm-step-card-header">
        <div class="lm-step-card-title-wrap">
            <span class="lm-step-badge">3</span>
            <div>
                <h3 class="lm-step-title"><i class="fa fa-sliders text-warning"></i> {{ $lmText('Installment Terms & Financing Structure', 'លក្ខខណ្ឌកម្ចី និងរចនាសម្ព័ន្ធការប្រាក់') }}</h3>
                <p class="lm-step-subtitle">{{ $lmText('Define principal financing amount, interest calculation method, duration, and billing frequency.', 'កំណត់ចំនួនទឹកប្រាក់ខ្ចី អត្រាការប្រាក់ រយៈពេលបង់ និងអ្នកទទួលខុសត្រូវ') }}</p>
            </div>
        </div>
        <div class="lm-step-header-actions">
            <span class="lm-badge-pill lm-badge-pill-amber">
                <i class="fa fa-shield"></i> {{ $lmText('Standard Plan', 'គម្រោងទូទៅ') }}
            </span>
        </div>
    </div>

    <div class="lm-step-card-body">
        <div class="row">
            <!-- Basic Identifiers -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Installment # / Ref Code', 'លេខកូដកម្ចី') }}</label>
                    <input type="text" name="loan_number" class="form-control lm-input-styled" placeholder="{{ $lmText('Auto-generated if left blank', 'បង្កើតស្វ័យប្រវត្ត') }}">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Business Location / Branch', 'សាខាអាជីវកម្ម') }}</label>
                    <select name="business_location_id" class="form-control lm-input-styled select2" style="width:100%">
                        <option value="">{{ $lmText('-- Select Branch --', '-- ជ្រើសរើសសាខា --') }}</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ (string) $id === (string) ($defaultLocationId ?? '') ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Contract / Start Date', 'កាលបរិច្ឆេទចាប់ផ្តើម') }} <span class="text-danger">*</span></label>
                    <input type="date" name="loan_date" class="form-control lm-input-styled" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Principal Financed Amount', 'ចំនួនប្រាក់ខ្ចីសរុប (Principal)') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="principal_amount_input" name="principal_amount" class="form-control lm-input-styled lm-input-highlight" min="0.01" required placeholder="0.00">
                </div>
            </div>

            <!-- Interest & Mode -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <div class="lm-label-with-shortcuts">
                        <label class="lm-field-label">{{ $lmText('Interest Rate (%)', 'អត្រាការប្រាក់ (%)') }}</label>
                        <div class="lm-quick-presets" id="interestRatePresets">
                            <button type="button" class="lm-preset-btn" data-val="0">0%</button>
                            <button type="button" class="lm-preset-btn" data-val="2.5">2.5%</button>
                            <button type="button" class="lm-preset-btn active" data-val="4">4%</button>
                            <button type="button" class="lm-preset-btn" data-val="5">5%</button>
                        </div>
                    </div>
                    <input type="number" step="0.01" name="interest_rate" id="interest_rate_input" class="form-control lm-input-styled" value="{{ old('interest_rate', 4) }}" min="0">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Interest Calculation Method', 'វិធីសាស្ត្រគណនាការប្រាក់') }} <span class="text-danger">*</span></label>
                    <select name="interest_type" id="interest_type_select" class="form-control lm-input-styled">
                        <option value="flat" selected>{{ $lmText('Flat Rate (បង់ថេរ)', 'បង់ថេរ (Flat Rate)') }}</option>
                        <option value="reducing_balance">{{ $lmText('Reducing Balance (បង់ថយ)', 'បង់ថយ (Reducing Balance)') }}</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <div class="lm-label-with-shortcuts">
                        <label class="lm-field-label">{{ $lmText('Duration (Months)', 'រយៈពេល (ខែ)') }} <span class="text-danger">*</span></label>
                        <div class="lm-quick-presets" id="durationPresets">
                            <button type="button" class="lm-preset-btn" data-val="3">3m</button>
                            <button type="button" class="lm-preset-btn" data-val="6">6m</button>
                            <button type="button" class="lm-preset-btn active" data-val="12">12m</button>
                            <button type="button" class="lm-preset-btn" data-val="18">18m</button>
                            <button type="button" class="lm-preset-btn" data-val="24">24m</button>
                        </div>
                    </div>
                    <input type="number" name="duration_months" id="duration_months_input" class="form-control lm-input-styled" min="1" max="360" value="12" required>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Payment Frequency', 'ប្រេកង់នៃការបង់') }} <span class="text-danger">*</span></label>
                    <select name="payment_frequency" id="payment_frequency_select" class="form-control lm-input-styled">
                        <option value="monthly" selected>{{ $lmText('Monthly (ប្រចាំខែ)', 'ប្រចាំខែ (Monthly)') }}</option>
                        <option value="weekly">{{ $lmText('Weekly (ប្រចាំសប្តាហ៍)', 'ប្រចាំសប្តាហ៍ (Weekly)') }}</option>
                        <option value="daily">{{ $lmText('Daily (ប្រចាំថ្ងៃ)', 'ប្រចាំថ្ងៃ (Daily)') }}</option>
                    </select>
                </div>
            </div>

            <!-- Currency & Collectors -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('First Due Date', 'ថ្ងៃត្រូវបង់ដំបូង') }} <span class="text-danger">*</span></label>
                    <input type="date" name="first_due_date" id="first_due_date_input" class="form-control lm-input-styled" value="{{ Carbon\Carbon::today()->addMonth()->format('Y-m-d') }}" required>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Currency', 'រូបិយប័ណ្ណ') }} <span class="text-danger">*</span></label>
                    <select name="currency" class="form-control lm-input-styled">
                        <option value="USD" selected>USD ($)</option>
                        <option value="KHR">KHR (៛)</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Exchange Rate', 'អត្រាប្តូរប្រាក់') }}</label>
                    <input type="number" step="0.0001" name="exchange_rate" class="form-control lm-input-styled" value="1">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Assigned Loan Officer / Collector', 'បុគ្គលិកទទួលបន្ទុក / ប្រមូលប្រាក់') }}</label>
                    <select name="assigned_collector_id" class="form-control lm-input-styled select2" style="width:100%">
                        <option value="">{{ $lmText('-- Unassigned --', '-- មិនទាន់ចាត់តាំង --') }}</option>
                        @foreach($collectors as $c)
                            <option value="{{ $c->id }}" {{ (string) $c->id === (string) ($defaultCollectorId ?? '') ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Penalty & Remarks -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Overdue Penalty Type', 'ប្រភេទពិន័យយឺតយ៉ាវ') }}</label>
                    <select name="penalty_type" class="form-control lm-input-styled">
                        <option value="fixed">{{ $lmText('Fixed Amount ($ / ៛)', 'ទឹកប្រាក់ថេរ') }}</option>
                        <option value="percentage">{{ $lmText('Percentage (%)', 'ភាគរយ (%)') }}</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Penalty Amount / Rate', 'ចំនួនប្រាក់ពិន័យ') }}</label>
                    <input type="number" step="0.01" name="penalty_amount" class="form-control lm-input-styled" value="0" min="0">
                </div>
            </div>

            <div class="col-sm-12 col-md-6">
                <div class="form-group lm-form-group">
                    <label class="lm-field-label">{{ $lmText('Installment Memo / Note', 'ចំណាំបន្ថែម') }}</label>
                    <input name="note" class="form-control lm-input-styled" placeholder="{{ $lmText('Special conditions, guarantor details, repayment notes...', 'កំណត់ចំណាំផ្សេងៗ...') }}">
                </div>
            </div>
        </div>
    </div>
</div>
