  @extends('frontend.default.layouts.master')

  @section('title')
      403 {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
  @endsection

  @section('contents')
      <section class="tt-error-page tt-blog-section pt-5 position-relative bg-light-subtle">
          <div class="container">
              <div class="row g-3">
                  <div class="content-404 text-center h-100 my-auto">
                      <img src="{{ staticAsset('frontend/default/assets/img/website/403.png') }}" alt="not found"
                          class="img-fluid h-75 w-25">
                      <h2 class="mt-4">Forbidden</h2>
                      <p class="mb-6">You do not have permission to view this page.</p>
                      <a href="{{ env('APP_URL') }}" class="btn btn-secondary btn-md rounded-1">Back to Home Page</a>
                  </div>

              </div>
          </div>
      </section>
  @endsection
