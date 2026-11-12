@props(['amount'])
<span {{ $attributes }}>₱{{ number_format((float) $amount, 2) }}</span>
