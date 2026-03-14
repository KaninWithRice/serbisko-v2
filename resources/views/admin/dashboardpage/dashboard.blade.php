@extends('admin.layout')

@section('page_title', 'Dashboard')

@section('content')
<div>
        <div class="sticky top-0 z-10 bg-white py-1"> 
            <div class="inline-flex rounded-xl shadow-md border border-gray-100 mb-4 bg-[#F7FBF9]">
                @foreach(['All' => '', 'Grade 11' => 'Grade 11', 'Grade 12' => 'Grade 12'] as $label => $value)
                    <a href="{{ route('admin.dashboard', $value ? ['grade_level' => $value] : []) }}" 
                    class="px-6 py-2 rounded-xl font-semibold text-sm transition-all {{ request('grade_level') == $value ? 'bg-[#00568d] text-white' : 'text-[#00568d] hover:text-[#00568d]/50 transition-colors inline-block mt-auto' }}">
                    {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="mt-2">
            <div class="w-full"> 
                @include('admin.dashboardpage.partials.graphs')
            </div>
        </div>
        <div class="flex flex-col gap-8 mt-6">
                @include('admin.dashboardpage.partials.cards')
        </div>
        <div class="flex flex-col gap-8 mt-6"> 
            @include('admin.dashboardpage.partials.recent_table')
        </div>   
</div>    
@endsection