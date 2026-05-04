@if(isset($transaction) && $transaction->attachment->count() > 0)
    <div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column mb-3">
        <h5 class="font-weight-bold mb-3">Прикріплені файли</h5>
        <div>
            @foreach($transaction->attachment as $file)
                @php
                    $url = '/storage/' . $file->path . $file->name . '.' . $file->extension;
                @endphp
                <a href="{{ $url }}" target="_blank" class="text-primary d-inline-block text-truncate mb-2 mr-3" style="max-width: 300px; padding: 5px 10px; background: #f8f9fa; border-radius: 5px; border: 1px solid #e9ecef;" title="{{ $file->original_name }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-paperclip mr-1" viewBox="0 0 16 16">
                        <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z"/>
                    </svg>
                    {{ $file->original_name }}
                </a>
            @endforeach
        </div>
    </div>
@endif
