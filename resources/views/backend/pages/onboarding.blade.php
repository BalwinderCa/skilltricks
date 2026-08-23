@extends('backend.layouts.master')

@section('title')
    {{ localize('Calibration') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-3">{{ localize('Let us calibrate SkillTricks to your organization') }}</h4>

                            <div id="oi-thread" class="mb-3">
                                @foreach($turns as $turn)
                                    <div class="mb-3">
                                        <p class="text-muted mb-1">{{ $turn['question'] }}</p>
                                        <p class="mb-0">{{ $turn['answer'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div id="oi-ask">
                                <p id="oi-question" class="fw-semibold">{{ $question }}</p>
                                <textarea id="oi-answer" class="form-control mb-2" rows="3"
                                          placeholder="{{ localize('Type your answer...') }}"></textarea>
                                <button id="oi-send" class="btn btn-primary">{{ localize('Send') }}</button>
                            </div>

                            <div id="oi-card" class="d-none">
                                <h5 class="mb-2">{{ localize('Here is what we heard') }}</h5>
                                <ul id="oi-bullets" class="mb-3"></ul>
                                <form method="POST" action="{{ route('onboarding.confirm') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        {{ localize('Confirm & Begin Strategic Mapping') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
<script>
(function () {
    const thread   = document.getElementById('oi-thread');
    const askBox   = document.getElementById('oi-ask');
    const card     = document.getElementById('oi-card');
    const bullets  = document.getElementById('oi-bullets');
    const questionEl = document.getElementById('oi-question');
    const answerEl = document.getElementById('oi-answer');
    const sendBtn  = document.getElementById('oi-send');

    function appendTurn(question, answer) {
        const block = document.createElement('div');
        block.className = 'mb-3';
        const q = document.createElement('p');
        q.className = 'text-muted mb-1';
        q.textContent = question;
        const a = document.createElement('p');
        a.className = 'mb-0';
        a.textContent = answer;
        block.append(q, a);
        thread.append(block);
    }

    sendBtn.addEventListener('click', async function () {
        const answer = answerEl.value.trim();
        if (!answer) return;

        const question = questionEl.textContent;
        sendBtn.disabled = true;

        try {
            const res = await fetch("{{ route('onboarding.answer') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ answer: answer }),
            });

            const data = await res.json();
            appendTurn(question, answer);
            answerEl.value = '';

            if (data.done) {
                askBox.classList.add('d-none');
                (data.profile.summary_bullets || []).forEach(function (text) {
                    const li = document.createElement('li');
                    li.textContent = text;
                    bullets.append(li);
                });
                card.classList.remove('d-none');
            } else {
                questionEl.textContent = data.question;
            }
        } finally {
            sendBtn.disabled = false;
        }
    });
})();
</script>
@endsection
