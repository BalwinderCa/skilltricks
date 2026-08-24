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

                            {{-- $profile is set once the interview has been summarised. Rendering
                                 the card server-side means a reload resumes at the confirmation
                                 step instead of dropping back to the seed question. --}}
                            <div id="oi-ask" class="{{ $profile ? 'd-none' : '' }}">
                                <p id="oi-question" class="fw-semibold">{{ $question }}</p>
                                <textarea id="oi-answer" class="form-control mb-2" rows="3"
                                          placeholder="{{ localize('Type your answer...') }}"></textarea>
                                <button id="oi-send" class="btn btn-primary">{{ localize('Send') }}</button>
                                <div id="oi-error" class="alert alert-danger mt-2 d-none" role="alert"></div>
                            </div>

                            <div id="oi-card" class="{{ $profile ? '' : 'd-none' }}">
                                <h5 class="mb-2">{{ localize('Here is what we heard') }}</h5>
                                <ul id="oi-bullets" class="mb-3">
                                    @foreach(($profile['summary_bullets'] ?? []) as $bullet)
                                        <li>{{ $bullet }}</li>
                                    @endforeach
                                </ul>
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
    const errorEl  = document.getElementById('oi-error');

    function showError(message) {
        errorEl.textContent = message;
        errorEl.classList.remove('d-none');
    }

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

            // A 429 from the throttle, a 419 CSRF expiry, or a 500 all return a
            // body with no question. Without these guards the literal string
            // "undefined" was rendered as the next question.
            if (!res.ok) {
                showError("{{ localize('Something went wrong. Please try again in a moment.') }}");
                return;
            }

            const data = await res.json().catch(function () { return null; });

            if (!data || (!data.done && !data.question)) {
                showError("{{ localize('Something went wrong. Please try again in a moment.') }}");
                return;
            }

            errorEl.classList.add('d-none');
            appendTurn(question, answer);
            answerEl.value = '';

            if (data.done) {
                askBox.classList.add('d-none');
                bullets.replaceChildren();
                ((data.profile || {}).summary_bullets || []).forEach(function (text) {
                    const li = document.createElement('li');
                    li.textContent = text;
                    bullets.append(li);
                });
                card.classList.remove('d-none');
            } else {
                questionEl.textContent = data.question;
            }
        } catch (e) {
            showError("{{ localize('Something went wrong. Please try again in a moment.') }}");
        } finally {
            sendBtn.disabled = false;
        }
    });
})();
</script>
@endsection
