@php
    $loanLanguage = session('user.language', config('app.locale'));
    $lmIsKhmer = $loanLanguage === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

<div class="lm-step-card lm-step-card-blue" id="sectionCustomer">
    <div class="lm-step-card-header">
        <div class="lm-step-card-title-wrap">
            <span class="lm-step-badge">1</span>
            <div>
                <h3 class="lm-step-title"><i class="fa fa-user-circle text-primary"></i> {{ $lmText('Customer Information & Identity', 'ព័ត៌មានអតិថិជន និងអត្តសញ្ញាណ') }}</h3>
                <p class="lm-step-subtitle">{{ $lmText('Search an existing customer or scan / upload an ID card to auto-fill customer details.', 'ស្វែងរកអតិថិជនចាស់ ឬថត/បញ្ចូលអត្តសញ្ញាណប័ណ្ណដើម្បីបំពេញទិន្នន័យស្វ័យប្រវត្តិ') }}</p>
            </div>
        </div>
        <div class="lm-step-header-actions">
            <button type="button" class="btn btn-default btn-xs lm-btn-clean" id="btnClearCustomer" title="{{ $lmText('Clear Customer', 'សម្អាតអតិថិជន') }}">
                <i class="fa fa-refresh"></i> {{ $lmText('Reset Customer', 'សម្អាតទិន្នន័យ') }}
            </button>
        </div>
    </div>

    <div class="lm-step-card-body">
        <div class="row">
            <!-- Customer Search & OCR Trigger -->
            <div class="col-md-6 col-sm-12">
                <div class="lm-input-group-custom">
                    <label class="lm-input-label"><i class="fa fa-search text-muted"></i> {{ $lmText('Search Existing Customer', 'ស្វែងរកអតិថិជនចាស់') }}</label>
                    <div class="lm-customer-search-wrap">
                        <input type="text" id="customerSearchInput" class="form-control lm-input-styled" placeholder="{{ $lmText('Type name, phone number, or ID card to search...', 'វាយបញ្ចូលឈ្មោះ លេខទូរស័ព្ទ ឬលេខអត្តសញ្ញាណប័ណ្ណ...') }}" autocomplete="off">
                        <div class="lm-customer-search-results"></div>
                    </div>
                    <small class="text-muted"><i class="fa fa-info-circle"></i> {{ $lmText('Type at least 2 characters to trigger smart lookup', 'វាយយ៉ាងតិច ២ តួអក្សរដើម្បីស្វែងរក') }}</small>
                </div>
            </div>

            <div class="col-md-6 col-sm-12">
                <div class="lm-input-group-custom">
                    <label class="lm-input-label"><i class="fa fa-id-card text-muted"></i> {{ $lmText('ID Card Smart OCR & Capture', 'ស្កេនអត្តសញ្ញាណប័ណ្ណស្វ័យប្រវត្តិ') }}</label>
                    <div class="lm-ocr-action-bar">
                        <label class="btn btn-default btn-sm lm-ocr-btn" for="customer_id_card_camera_input">
                            <i class="fa fa-camera text-primary"></i> <span>{{ $lmText('Take Photo', 'ថតរូប') }}</span>
                        </label>
                        <label class="btn btn-default btn-sm lm-ocr-btn" for="customer_id_card_photo_input">
                            <i class="fa fa-upload text-success"></i> <span>{{ $lmText('Upload ID', 'បញ្ចូលរូប') }}</span>
                        </label>
                    </div>

                    <input type="file" id="customer_id_card_camera_input" accept="image/*" capture="environment" style="display:none;">
                    <input type="file" id="customer_id_card_photo_input" accept="image/*" style="display:none;">
                    <input type="hidden" name="id_card_ocr_raw_text" id="id_card_ocr_raw_text_input">
                    <input type="hidden" name="id_card_ocr_fields[id_card_number]" id="id_card_ocr_number_input">
                    <input type="hidden" name="id_card_ocr_fields[khmer_name]" id="id_card_ocr_khmer_name_input">
                    <input type="hidden" name="id_card_ocr_fields[english_name]" id="id_card_ocr_english_name_input">
                    <input type="hidden" name="id_card_ocr_fields[address]" id="id_card_ocr_address_input">
                    
                    <div id="customer_id_card_photo_preview" class="lm-id-preview-box" style="display:none;">
                        <img src="" alt="ID Card">
                    </div>
                    <p id="id_card_ocr_status" class="help-block lm-ocr-status-text"></p>
                </div>
            </div>
        </div>

        <input type="hidden" name="customer_id" id="customer_id_input" value="">

        <!-- Customer Detailed Profile Fields -->
        <div id="customer_info_fields" class="lm-customer-profile-block" style="display:none;">
            <div class="lm-divider-title">
                <span><i class="fa fa-address-card-o"></i> {{ $lmText('Customer Profile Details', 'ព័ត៌មានលម្អិតអតិថិជន') }}</span>
            </div>
            
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group lm-form-group">
                        <label class="lm-field-label">{{ $lmText('Customer Group', 'ក្រុមអតិថិជន') }}</label>
                        <input name="customer_group_name" class="form-control lm-input-styled" value="រំលស់">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group lm-form-group">
                        <label class="lm-field-label">{{ $lmText('Name in Khmer', 'ឈ្មោះជាភាសាខ្មែរ') }} <span class="text-danger">*</span></label>
                        <input type="text" name="customer_khmer_name" id="customer_khmer_name_input" class="form-control lm-input-styled" required placeholder="{{ $lmText('Khmer name', 'ឈ្មោះខ្មែរ') }}">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group lm-form-group">
                        <label class="lm-field-label">{{ $lmText('Name in English', 'ឈ្មោះជាអង់គ្លេស') }} <span class="text-danger">*</span></label>
                        <input type="text" name="customer_english_name" id="customer_english_name_input" class="form-control lm-input-styled" required placeholder="{{ $lmText('English name', 'ឈ្មោះអង់គ្លេស') }}">
                        <input type="hidden" name="customer_name" id="customer_name_input">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group lm-form-group">
                        <label class="lm-field-label">{{ $lmText('Phone Number', 'លេខទូរស័ព្ទ') }}</label>
                        <div class="input-group lm-input-group">
                            <input type="text" name="customer_phone" id="customer_phone_input" class="form-control lm-input-styled" placeholder="012 345 678">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default lm-btn-addon" id="btnShowAlternatePhone" title="{{ $lmText('Add secondary phone', 'បន្ថែមលេខទូរស័ព្ទទី២') }}">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3" id="alternate_phone_group" style="display:none;">
                    <div class="form-group lm-form-group">
                        <label class="lm-field-label">{{ $lmText('Alternate Phone', 'លេខទូរស័ព្ទទី២') }}</label>
                        <input type="text" name="alternate_phone" id="alternate_phone_input" class="form-control lm-input-styled" placeholder="{{ $lmText('Secondary phone', 'លេខទូរស័ព្ទបន្ថែម') }}">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group lm-form-group">
                        <label class="lm-field-label">{{ $lmText('ID Card / Passport No.', 'លេខអត្តសញ្ញាណប័ណ្ណ') }}</label>
                        <input type="text" name="id_card_number" id="customer_id_card_input" class="form-control lm-input-styled" placeholder="012345678">
                    </div>
                </div>
                <div class="col-sm-6 col-md-6" style="display:none;">
                    <div class="form-group lm-form-group">
                        <label class="lm-field-label">{{ $lmText('ID Card Address', 'អាសយដ្ឋានលើអត្តសញ្ញាណប័ណ្ណ') }}</label>
                        <input type="hidden" name="customer_address" id="customer_address_input" class="form-control lm-input-styled" placeholder="Address">
                    </div>
                </div>
            </div>
        </div>

        <!-- KYC Documents & Extra Attachments -->
        <div class="lm-doc-section">
            <div class="lm-divider-title">
                <span><i class="fa fa-paperclip"></i> {{ $lmText('KYC Documents & Attachments', 'ឯកសារភ្ជាប់ និងរូបភាពផ្ទៀងផ្ទាត់ (KYC)') }}</span>
                <small class="text-muted">({{ $lmText('Photos, PDFs, Text files, Contracts', 'រូបថត, ឯកសារ PDF, កិច្ចសន្យា') }})</small>
            </div>
            
            <div class="lm-doc-grid" id="lmDocGrid">
                <label class="lm-doc-add" for="lmDocInput">
                    <i class="fa fa-cloud-upload"></i>
                    <span>{{ $lmText('Upload File', 'បញ្ចូលឯកសារ') }}</span>
                </label>
            </div>
            <input type="file" id="lmDocInput" accept="image/*,.pdf,.txt,.csv,.doc,.docx" multiple style="display:none;">

            <div class="row" style="margin-top:12px;">
                <div class="col-sm-12 col-md-6">
                    <label class="lm-field-label-sub">{{ $lmText('Document Notes / Telegram Memo', 'កំណត់សម្គាល់ឯកសារ / ផ្ញើទៅ Telegram') }}</label>
                    <textarea name="document_text" class="form-control lm-textarea-styled" rows="2" placeholder="{{ $lmText('Write notes, guarantor details, or extra memo to notify...', 'សរសេរកំណត់ចំណាំ ឬព័ត៌មានអ្នកធានា...') }}"></textarea>
                </div>
                <div class="col-sm-12 col-md-6">
                    <label class="lm-field-label-sub">{{ $lmText('External Cloud / Document Links', 'តំណភ្ជាប់ឯកសារ Cloud / Drive Links') }}</label>
                    <div id="lmDocumentLinks">
                        <div class="input-group lm-input-group" style="margin-bottom:6px;">
                            <input type="url" name="document_links[]" class="form-control lm-input-styled" placeholder="https://drive.google.com/...">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default lm-btn-addon" id="btnAddDocumentLink" title="{{ $lmText('Add another link', 'បន្ថែមតំណភ្ជាប់') }}">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="lm-doc-paste-hint" id="lmDocPasteHint">
                <i class="fa fa-keyboard-o"></i>
                <span>{{ $lmText('Smart Clipboard: You can paste screenshots directly with Ctrl+V (Cmd+V) anywhere on this form.', 'គន្លឹះរហ័ស៖ អ្នកអាចចុច Ctrl+V ដើម្បីបិទភ្ជាប់រូបភាពភ្លាមៗពី Clipboard') }}</span>
            </div>
        </div>
    </div>
</div>
