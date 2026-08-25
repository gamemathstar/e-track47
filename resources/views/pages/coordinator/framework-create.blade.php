@php use App\Models\Framework; @endphp
@extends('layouts.app')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
        .framework-card {
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.15s;
        }
        .framework-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .icon-container {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            background-color: rgba(0, 133, 80, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #008550;
        }
        .form-input, .form-textarea, .form-select {
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            color: #0f172a;
            transition: all 0.15s;
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #008550;
            box-shadow: 0 0 0 3px rgba(0, 133, 80, 0.1);
        }
        .form-label {
            color: #334155;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .btn-primary-custom {
            background-color: #008550;
            color: white;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            transition: background-color 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            cursor: pointer;
        }
        .btn-primary-custom:hover {
            background-color: rgba(0, 133, 80, 0.9);
        }
        .btn-secondary-custom {
            background-color: #0f172a;
            color: white;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            transition: background-color 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            cursor: pointer;
        }
        .btn-secondary-custom:hover {
            background-color: #1e293b;
        }
        .tip-box {
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: rgba(0, 133, 80, 0.05);
            border: 1px solid rgba(0, 133, 80, 0.1);
        }
        .preview-box {
            aspect-ratio: 16 / 9;
            width: 100%;
            border-radius: 0.5rem;
            background-color: #f1f5f9;
            overflow: hidden;
            margin-bottom: 0.5rem;
            position: relative;
        }
        .preview-box::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom right, rgba(0, 133, 80, 0.05), rgba(0, 133, 80, 0.2));
        }
        .preview-icon {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 4rem;
        }
    </style>
@endsection

@section('content')
<div class="content">
    <div style="padding: 3rem 1.5rem; display: flex; justify-content: center;">
        <div style="max-width: 1100px; width: 100%; flex: 1;">
            <!-- Header Section -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 3rem; text-align: center;">
                <h1 style="font-size: 2.25rem; font-weight: 900; line-height: 1.2; color: #0f172a; margin: 0;">
                    Initialize New Framework
                </h1>
                <p style="font-size: 1.125rem; color: #475569; max-width: 42rem; margin: 0 auto;">
                    Choose how you want to set up your performance framework for the upcoming cycle. You can start fresh or build upon previous success.
                </p>
            </div>

            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                <!-- Create from Scratch Card -->
                <div class="framework-card" style="display: flex; flex-direction: column; height: 100%;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="icon-container">
                            <span class="material-symbols-outlined" style="font-size: 1.875rem;">add_circle</span>
                        </div>
                        <div>
                            <h3 style="font-size: 1.25rem; font-weight: bold; color: #0f172a; margin: 0 0 0.25rem 0;">Create from Scratch</h3>
                            <p style="font-size: 0.875rem; color: #64748b; margin: 0;">Define all parameters for a brand new cycle.</p>
                        </div>
                    </div>
                    <form action="{{ route('frameworks.store') }}" method="POST" id="framework-form-scratch" style="display: flex; flex-direction: column; gap: 1.25rem; flex-grow: 1;">
                        @csrf
                        <input type="hidden" name="creation_method" value="scratch">
                        <label class="form-label" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span>Year</span>
                            <input type="number" name="year" class="form-input" 
                                   placeholder="e.g., 2024" 
                                   min="2000" max="2100" 
                                   value="{{ date('Y') }}" 
                                   required>
                            @error('year')
                                <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="form-label" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span>Framework Title</span>
                            <input type="text" name="title" class="form-input" 
                                   placeholder="e.g., 2024 Annual Performance Plan" 
                                   required>
                            @error('title')
                                <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="form-label" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span>Description</span>
                            <textarea name="description" class="form-textarea" 
                                      placeholder="Key objectives and focus areas for this cycle..." 
                                      rows="3"></textarea>
                        </label>
                        <div style="margin-top: auto; padding-top: 2rem;">
                            <button type="submit" class="btn-primary-custom">
                                Continue
                                <span class="material-symbols-outlined" style="font-size: 0.875rem; transition: transform 0.15s;">arrow_forward</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Inherit from Previous Year Card -->
                <div class="framework-card" style="display: flex; flex-direction: column; height: 100%;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="icon-container">
                            <span class="material-symbols-outlined" style="font-size: 1.875rem;">history</span>
                        </div>
                        <div>
                            <h3 style="font-size: 1.25rem; font-weight: bold; color: #0f172a; margin: 0 0 0.25rem 0;">Inherit from Previous Year</h3>
                            <p style="font-size: 0.875rem; color: #64748b; margin: 0;">Carry over goals and structures from a past year.</p>
                        </div>
                    </div>
                    <form action="{{ route('frameworks.confirm-inherit') }}" method="POST" id="framework-form-inherit" style="display: flex; flex-direction: column; gap: 1.25rem; flex-grow: 1;">
                        @csrf
                        <input type="hidden" name="creation_method" value="inherit">
                        <div class="preview-box">
                            <div class="preview-icon">
                                <span class="material-symbols-outlined">file_copy</span>
                            </div>
                        </div>
                        <label class="form-label" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span>Select Source Framework</span>
                            <select name="source_framework_id" class="form-select" id="source_framework_id" required>
                                <option value="">Choose a framework...</option>
                                @if(isset($existingFrameworks) && $existingFrameworks->count() > 0)
                                    @foreach($existingFrameworks as $framework)
                                        <option value="{{ $framework->id }}">
                                            {{ $framework->year }} - {{ $framework->title }}
                                            @if($framework->isActive())
                                                (Active)
                                            @elseif($framework->isArchived())
                                                (Archived)
                                            @endif
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No frameworks available</option>
                                @endif
                            </select>
                            @error('source_framework_id')
                                <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                            @if(!isset($existingFrameworks) || $existingFrameworks->count() === 0)
                                <span style="color: #64748b; font-size: 0.75rem; font-style: italic;">
                                    No existing frameworks found. Please create a framework from scratch first.
                                </span>
                            @endif
                        </label>
                        <label class="form-label" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span>Year</span>
                            <input type="number" name="year" class="form-input" 
                                   placeholder="e.g., 2025" 
                                   min="2000" max="2100" 
                                   value="{{ date('Y') + 1 }}" 
                                   required>
                            @error('year')
                                <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="form-label" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span>Framework Title</span>
                            <input type="text" name="title" class="form-input" 
                                   placeholder="e.g., 2025 Annual Performance Plan" 
                                   required>
                            @error('title')
                                <span style="color: #ef4444; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="form-label" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span>Description</span>
                            <textarea name="description" class="form-textarea" 
                                      placeholder="Key objectives and focus areas for this cycle..." 
                                      rows="3"></textarea>
                        </label>
                        <div class="tip-box">
                            <p style="font-size: 0.875rem; color: #475569; line-height: 1.625; margin: 0;">
                                <span style="font-weight: bold; color: #008550;">Pro tip:</span> On the next step you can choose full structure or sectors only, and pick which sectors to carry over.
                            </p>
                        </div>
                        <div style="margin-top: auto; padding-top: 2rem;">
                            <button type="submit" class="btn-secondary-custom">
                                Continue
                                <span class="material-symbols-outlined" style="font-size: 0.875rem; transition: transform 0.15s;">arrow_forward</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Text -->
            <div style="text-align: center; margin-top: 3rem;">
                <p style="font-size: 0.875rem; color: #64748b;">
                    Need help deciding? 
                    <a href="#" style="color: #008550; font-weight: 500; text-decoration: underline;">View Framework Templates Documentation</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    // Add hover effect for arrow icons
    document.querySelectorAll('.btn-primary-custom, .btn-secondary-custom').forEach(button => {
        button.addEventListener('mouseenter', function() {
            const arrow = this.querySelector('.material-symbols-outlined');
            if (arrow) {
                arrow.style.transform = 'translateX(4px)';
            }
        });
        button.addEventListener('mouseleave', function() {
            const arrow = this.querySelector('.material-symbols-outlined');
            if (arrow) {
                arrow.style.transform = 'translateX(0)';
            }
        });
    });

    // Auto-populate title based on selected source framework and year
    document.addEventListener('DOMContentLoaded', function() {
        const sourceFrameworkSelect = document.getElementById('source_framework_id');
        const yearInput = document.querySelector('#framework-form-inherit input[name="year"]');
        const titleInput = document.querySelector('#framework-form-inherit input[name="title"]');
        
        if (sourceFrameworkSelect && yearInput && titleInput) {
            // Update title when year or source framework changes
            function updateTitle() {
                const selectedOption = sourceFrameworkSelect.options[sourceFrameworkSelect.selectedIndex];
                const year = yearInput.value;
                
                if (selectedOption.value && year) {
                    // Extract framework title from option text (format: "2024 - Title (Status)")
                    const optionText = selectedOption.text;
                    const match = optionText.match(/^\d+\s*-\s*(.+?)(?:\s*\([^)]+\))?$/);
                    
                    if (match) {
                        const baseTitle = match[1].trim();
                        // Replace year in title if it exists, otherwise prepend new year
                        const newTitle = baseTitle.replace(/\d{4}/, year) || `${year} ${baseTitle}`;
                        titleInput.value = newTitle;
                    } else {
                        titleInput.value = `${year} Annual Performance Plan`;
                    }
                }
            }
            
            sourceFrameworkSelect.addEventListener('change', updateTitle);
            yearInput.addEventListener('input', updateTitle);
        }
    });
</script>
@endsection
