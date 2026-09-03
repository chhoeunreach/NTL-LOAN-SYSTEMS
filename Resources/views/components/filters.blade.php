<div class="box box-solid {{ $class ?? '' }}">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $title ?? 'Filters' }}</h3>
        <div class="box-tools pull-right">
            {{ $tool ?? '' }}
        </div>
    </div>
    <div class="box-body">
        {{ $slot }}
    </div>
</div>
