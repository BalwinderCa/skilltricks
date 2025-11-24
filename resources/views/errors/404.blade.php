  @extends('frontend.default.layouts.master')

  @section('title')
      404 {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
  @endsection

  @section('contents')
      <section class="tt-error-page tt-blog-section pt-5 position-relative bg-light-subtle">
          <div class="container">
              <div class="row g-3">
                  <div class="content-404 text-center h-100 my-auto">
                      <img src="{{ staticAsset('frontend/default/assets/img/website/404.png') }}" alt="not found"
                          class="img-fluid h-75 w-25">
                      <h2 class="mt-4">Sorry, Something Went Wrong</h2>
                      <p class="mb-6">The page you are looking for might have been removed had its name changed or is
                          temporarily unavailable.</p>
                      <a href="{{ env('APP_URL') }}" class="btn btn-secondary btn-md rounded-1">Back to Home Page</a>
                  </div>

              </div>
          </div>
      </section>
  @endsection
