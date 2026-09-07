@php
    $lmIsKhmer = session('user.language', config('app.locale')) === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

@component('components.widget', ['class' => 'box-primary', 'title' => $definition['title'] ?? $lmText('Due Today Installments', 'កម្ចីដល់ថ្ងៃត្រូវបង់ថ្ងៃនេះ')])
    <div class="table-responsive">
        <table class="lm-table-dense table table-striped table-bordered table-hover" id="loanCollectionTable">
            <thead>
                <tr style="background: #f8fafc; color: #475569;">
                    <th style="width: 70px; text-align: center;" class="no-export">{{ $lmText('Action', 'សកម្មភាព') }}</th>
                    <th>{{ $lmText('Installment #', 'លេខកិច្ចសន្យា') }}</th>
                    <th>{{ $lmText('Customer', 'អតិថិជន') }}</th>
                    <th>{{ $lmText('Phone', 'ទូរស័ព្ទ') }}</th>
                    <th style="text-align: center;">{{ $lmText('Status', 'ស្ថានភាព') }}</th>
                    <th style="text-align: center;">{{ $lmText('Risk', 'ហានិភ័យ') }}</th>
                    <th>{{ $lmText('Bucket', 'កម្រិត') }}</th>
                    <th style="text-align: center;">{{ $lmText('DPD', 'ថ្ងៃហួស') }}</th>
                    <th>{{ $lmText('PTP Promise', 'សន្យាបង់') }}</th>
                    <th class="text-right">{{ $lmText('Balance', 'សមតុល្យនៅសល់') }}</th>
                    <th>{{ $lmText('Next Follow-up', 'តាមដានបន្ទាប់') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    @php
                        $status = $loan->collection_status ?? $loan->status ?? 'active';
                        $risk = $loan->risk_level ?? 'normal';
                    @endphp
                    <tr>
                        <td style="text-align: center; vertical-align: middle;">
                            @if(Route::has('loan-management.loans.view'))
                                <a class="btn btn-xs btn-info" href="{{ route('loan-management.loans.view', $loan->id) }}" title="{{ $lmText('View Installment Details', 'មើលព័ត៌មានលម្អិត') }}">
                                    <i class="fa fa-eye"></i> View
                                </a>
                            @endif
                        </td>
                        <td>
                            @if(Route::has('loan-management.loans.view'))
                                <a href="{{ route('loan-management.loans.view', $loan->id) }}" style="font-weight: 700; color: #0284c7; text-decoration: none;">
                                    {{ $loan->loan_number ?? $loan->id }}
                                </a>
                            @else
                                <strong>{{ $loan->loan_number ?? $loan->id }}</strong>
                            @endif
                        </td>
                        <td><strong>{{ $loan->customer_name_snapshot ?? '-' }}</strong></td>
                        <td>{{ $loan->customer_phone_snapshot ?? '-' }}</td>
                        <td style="text-align: center;">
                            <span class="{{ $badges::badgeClass($status, $risk) }}" style="font-size: 10.5px;">
                                {{ $options['statuses'][$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="{{ $badges::badgeClass($status, $risk) }}" style="font-size: 10.5px;">
                                {{ $options['riskLevels'][$risk] ?? ucfirst(str_replace('_', ' ', $risk)) }}
                            </span>
                        </td>
                        <td>{{ $options['buckets'][$loan->overdue_bucket ?? 'current'] ?? '-' }}</td>
                        <td style="text-align: center;">
                            @php $dpd = (int) ($loan->days_past_due ?? 0); @endphp
                            <span class="badge {{ $dpd > 0 ? 'bg-red' : 'bg-gray' }}" style="font-size: 11px;">{{ $dpd }}</span>
                        </td>
                        <td>
                            @if(!empty($loan->ptp_date))
                                <strong>{{ $loan->ptp_date }}</strong>
                                <div style="font-size: 11px; color: #d97706;">${{ number_format((float)($loan->ptp_amount ?? 0), 2) }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-right" style="font-weight: 700; color: #0f172a;">
                            ${{ number_format((float)($loan->balance_amount ?? 0), 2) }}
                        </td>
                        <td style="font-size: 11.5px; color: #64748b;">{{ $loan->next_followup_at ?? '-' }}</td>
                    </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($loans, 'links') && $loans->hasPages())
        <div style="margin-top: 10px;">
            {{ $loans->links() }}
        </div>
    @endif
@endcomponent
