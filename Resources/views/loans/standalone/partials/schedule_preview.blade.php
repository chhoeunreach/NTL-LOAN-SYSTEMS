@php
    $loanLanguage = session('user.language', config('app.locale'));
    $lmIsKhmer = $loanLanguage === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

<div class="lm-step-card lm-step-card-cyan" id="sectionSchedule">
    <div class="lm-step-card-header">
        <div class="lm-step-card-title-wrap">
            <span class="lm-step-badge lm-step-badge-icon"><i class="fa fa-table"></i></span>
            <div>
                <h3 class="lm-step-title"><i class="fa fa-calendar-check-o text-info"></i> {{ $lmText('Amortization Schedule Preview', 'កាលវិភាគបង់ប្រាក់សាកល្បង') }}</h3>
                <p class="lm-step-subtitle">{{ $lmText('Generate an instant breakdown of installment periods, principal portions, and interest before saving.', 'ពិនិត្យមើលកាលវិភាគបង់ប្រាក់លម្អិត ប្រាក់ដើម និងការប្រាក់តាមដំណាក់កាលនីមួយៗ') }}</p>
            </div>
        </div>
        <div class="lm-step-header-actions">
            <button type="button" class="btn btn-info btn-sm lm-btn-action" id="btnPreviewScheduleTop">
                <i class="fa fa-refresh"></i> {{ $lmText('Calculate Schedule', 'គណនាកាលវិភាគ') }}
            </button>
        </div>
    </div>

    <div class="lm-step-card-body">
        <div class="table-responsive lm-table-responsive-clean">
            <table class="table table-bordered table-hover lm-schedule-table" id="schedulePreviewTable">
                <thead>
                    <tr>
                        <th style="width:8%; text-align:center;">#</th>
                        <th style="width:20%;">{{ $lmText('Due Date', 'ថ្ងៃត្រូវបង់') }}</th>
                        <th style="width:18%; text-align:right;">{{ $lmText('Principal Due', 'ប្រាក់ដើម') }}</th>
                        <th style="width:18%; text-align:right;">{{ $lmText('Interest Due', 'ការប្រាក់') }}</th>
                        <th style="width:18%; text-align:right;">{{ $lmText('Total Period Payment', 'សរុបត្រូវបង់') }}</th>
                        <th style="width:18%; text-align:right;">{{ $lmText('Remaining Balance', 'សមតុល្យនៅសល់') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="lm-schedule-empty-state">
                        <td colspan="6" class="text-center text-muted" style="padding: 24px;">
                            <i class="fa fa-calculator" style="font-size: 24px; color: #94a3b8; display: block; margin-bottom: 6px;"></i>
                            <span>{{ $lmText('Click "Preview Schedule" or adjust terms above to generate repayment breakdown.', 'ចុច "Preview Schedule" ដើម្បីមើលតារាងកាលវិភាគបង់ប្រាក់') }}</span>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="lm-schedule-tfoot-row">
                        <th colspan="2" class="text-right lm-total-label">{{ $lmText('Grand Totals:', 'សរុបរួម:') }}</th>
                        <th class="text-right lm-stat-num">0.00</th>
                        <th class="text-right lm-stat-num">0.00</th>
                        <th class="text-right lm-stat-num">0.00</th>
                        <th class="text-right lm-stat-num">0.00</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
