@extends('backend.layouts.master')



@section('title')

    {{ localize('Update Profile') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}

@endsection



@section('contents')

    <section class="tt-section pt-4">

        <div class="container">

            <div class="row mb-4">

                <div class="col-12">

                    <div class="tt-page-header">

                        <div class="d-lg-flex align-items-center justify-content-lg-between">

                            <div class="tt-page-title mb-3 mb-lg-0">

                                <h1 class="h4 mb-lg-1">{{ localize('Update Profile') }}</h1>

                                <ol class="breadcrumb breadcrumb-angle text-muted">

                                    <li class="breadcrumb-item"><a

                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>

                                    </li>

                                    <li class="breadcrumb-item">{{ localize('Profile') }}</li>

                                </ol>

                            </div>

                            <div class="tt-action">
                                @if(!empty($user->user_type == 'customer'))
                                    @if($user->name && $user->phone && $user->company_name && $user->company_address && $user->number_employess && $user->chat_role_categories && $user->company_category && $user->about_company)

                                    @else
                                        <script>
                                            window.addEventListener('load', function () {
                                                alert('Please complete your profile to access the dashboard.');
                                            });
                                        </script>
                                    @endif

                                @endif
                            </div>

                        </div>

                    </div>

                </div>

            </div>





            <div class="row mb-4 g-4">



                <!--left sidebar-->

                <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">

                    <form action="{{ route('dashboard.profile.update') }}" method="POST">

                        @csrf

                        <input type="hidden" name="id" value="{{ $user->id }}">

                        <!--basic information start-->

                        <div class="card mb-4" id="section-1">

                            <div class="card-body">

                                <h5 class="mb-4">{{ localize('Basic Information') }}</h5>



                                <div class="mb-3">

                                    <label for="name" class="form-label">{{ localize('Name') }}<span class="text-danger">*</span></label>

                                    <input class="form-control" type="text" id="name"

                                        placeholder="{{ localize('Type your name') }}" name="name" required

                                        value="{{ $user->name }}">

                                </div>





                                <div class="mb-3">

                                    <label for="email" class="form-label">{{ localize('Email') }}<span class="text-danger">*</span></label>

                                    <input class="form-control" type="email" id="email"

                                        placeholder="{{ localize('Type your email') }}" name="email" required

                                        value="{{ $user->email }}" disabled>

                                </div>



                                <div class="mb-3">

                                    <label for="phone" class="form-label">{{ localize('Phone') }}<span class="text-danger">*</span></label>

                                    <input class="form-control" type="text" id="phone"

                                        placeholder="{{ localize('Type your phone') }}"

                                        name="phone"value="{{ $user->phone }}" required>

                                </div>



                                <div class="mb-3">

                                    <label class="form-label">{{ localize('Avatar') }}</label>

                                    <div class="tt-image-drop rounded">

                                        <span class="fw-semibold">{{ localize('Choose Avatar') }}</span>

                                        <!-- choose media -->

                                        <div class="tt-product-thumb show-selected-files mt-3">

                                            <div class="avatar avatar-xl cursor-pointer choose-media"

                                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"

                                                onclick="showMediaManager(this)" data-selection="single">

                                                <input type="hidden" name="avatar" value="{{ $user->avatar }}">

                                                <div class="no-avatar rounded-circle">

                                                    <span><i data-feather="plus"></i></span>

                                                </div>

                                            </div>

                                        </div>

                                        <!-- choose media -->

                                    </div>

                                </div>



                                <div class="mb-3">

                                    <label for="password" class="form-label">{{ localize('Password') }}</label>

                                    <input class="form-control" type="password" id="password"

                                        placeholder="{{ localize('Type password') }}" name="password">

                                </div>



                                <div class="mb-3">

                                    <label for="password_confirmation"

                                        class="form-label">{{ localize('Confirm Password') }}</label>

                                    <input class="form-control" type="password" id="password_confirmation"

                                        placeholder="{{ localize('Re-type password') }}" name="password_confirmation">

                                </div>


                            @if(!empty($user->user_type == 'customer'))


                                <h5 class="mb-4 mt-4">{{ localize('Company Information') }}</h5>

                                 <div class="mb-3">

                                    <label for="name" class="form-label">{{ localize('Company Name') }}<span class="text-danger">*</span></label>

                                    <input class="form-control" type="text" id="company_name"

                                        placeholder="{{ localize('Company Name') }}" name="company_name" required

                                        value="{{ old('company_name', $user->company_name ?? '') }}">

                                </div>

                                 <div class="mb-3">

                                    <label for="name" class="form-label">{{ localize('Company Address') }}<span class="text-danger">*</span></label>

                                    <input class="form-control" type="text" id="company_address"

                                        placeholder="{{ localize('Company Address') }}" name="company_address" required

                                        value="{{ old('company_address', $user->company_address ?? '') }}">

                                </div>

                                 <div class="mb-3">
                                    <label for="name" class="form-label">{{ localize('No.of employess') }}<span class="text-danger">*</span></label>
                                    <select class="form-control" name="number_employess" required>
                                        <option value="">Select</option>
                                        <option value="0-10" {{ $user->number_employess == '0-10' ? 'selected' : '' }}>0-10</option>
                                        <option value="10-20" {{ $user->number_employess == '10-20' ? 'selected' : '' }}>10-20</option>
                                        <option value="20-50" {{ $user->number_employess == '20-50' ? 'selected' : '' }}>20-50</option>
                                        <option value="50-100" {{ $user->number_employess == '50-100' ? 'selected' : '' }}>50-100</option>
                                        <option value="100-500" {{ $user->number_employess == '100-500' ? 'selected' : '' }}>100-500</option>
                                        <option value="500-1000" {{ $user->number_employess == '500-1000' ? 'selected' : '' }}>500-1000</option>
                                        <option value="1000-10000" {{ $user->number_employess == '1000-10000' ? 'selected' : '' }}>1000-10000</option>
                                    </select>

                                </div>

                                 <div class="mb-3">
                                    <label for="name" class="form-label">{{ localize('Role') }}<span class="text-danger">*</span></label>
                                    
                                    <select  class="form-control" name="chat_role_categories" required>
                                        <option value="">Select</option>
                                    @foreach($chatrolecategories as $vlaue)
                                        <option value="{{$vlaue->id}}" {{ $user->chat_role_categories == $vlaue->id ? 'selected' : '' }}>{{$vlaue->name}}</option>
                                    @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ localize('Company category') }}<span class="text-danger">*</span></label>

                                    <select class="form-control" name="company_category" required>
                                        <option value="">Select</option>

                                        <optgroup label="1. Technology">
                                            <option value="Software" {{ $user->company_category == 'Software' ? 'selected' : '' }}>Software</option>
                                            <option value="Hardware" {{ $user->company_category == 'Hardware' ? 'selected' : '' }}>Hardware</option>
                                            <option value="IT Services" {{ $user->company_category == 'IT Services' ? 'selected' : '' }}>IT Services</option>
                                            <option value="Artificial Intelligence" {{ $user->company_category == 'Artificial Intelligence' ? 'selected' : '' }}>Artificial Intelligence</option>
                                            <option value="Cybersecurity" {{ $user->company_category == 'Cybersecurity' ? 'selected' : '' }}>Cybersecurity</option>
                                            <option value="Cloud Computing" {{ $user->company_category == 'Cloud Computing' ? 'selected' : '' }}>Cloud Computing</option>
                                            <option value="Fintech" {{ $user->company_category == 'Fintech' ? 'selected' : '' }}>Fintech (Financial Technology)</option>
                                        </optgroup>

                                        <optgroup label="2. Healthcare">
                                            <option value="Pharmaceuticals" {{ $user->company_category == 'Pharmaceuticals' ? 'selected' : '' }}>Pharmaceuticals</option>
                                            <option value="Biotechnology" {{ $user->company_category == 'Biotechnology' ? 'selected' : '' }}>Biotechnology</option>
                                            <option value="Medical Devices" {{ $user->company_category == 'Medical Devices' ? 'selected' : '' }}>Medical Devices</option>
                                            <option value="Hospitals & Clinics" {{ $user->company_category == 'Hospitals & Clinics' ? 'selected' : '' }}>Hospitals & Clinics</option>
                                            <option value="Health Insurance" {{ $user->company_category == 'Health Insurance' ? 'selected' : '' }}>Health Insurance</option>
                                            <option value="Telemedicine" {{ $user->company_category == 'Telemedicine' ? 'selected' : '' }}>Telemedicine</option>
                                        </optgroup>

                                        <optgroup label="3. Finance">
                                            <option value="Banking" {{ $user->company_category == 'Banking' ? 'selected' : '' }}>Banking</option>
                                            <option value="Investment Services" {{ $user->company_category == 'Investment Services' ? 'selected' : '' }}>Investment Services</option>
                                            <option value="Insurance" {{ $user->company_category == 'Insurance' ? 'selected' : '' }}>Insurance</option>
                                            <option value="Accounting" {{ $user->company_category == 'Accounting' ? 'selected' : '' }}>Accounting</option>
                                            <option value="Venture Capital" {{ $user->company_category == 'Venture Capital' ? 'selected' : '' }}>Venture Capital / Private Equity</option>
                                        </optgroup>

                                        <optgroup label="4. Consumer Goods">
                                            <option value="Food & Beverage" {{ $user->company_category == 'Food & Beverage' ? 'selected' : '' }}>Food & Beverage</option>
                                            <option value="Clothing & Apparel" {{ $user->company_category == 'Clothing & Apparel' ? 'selected' : '' }}>Clothing & Apparel</option>
                                            <option value="Beauty & Personal Care" {{ $user->company_category == 'Beauty & Personal Care' ? 'selected' : '' }}>Beauty & Personal Care</option>
                                            <option value="Household Products" {{ $user->company_category == 'Household Products' ? 'selected' : '' }}>Household Products</option>
                                            <option value="Electronics" {{ $user->company_category == 'Electronics' ? 'selected' : '' }}>Electronics (Consumer)</option>
                                        </optgroup>

                                        <optgroup label="5. Retail">
                                            <option value="E-commerce" {{ $user->company_category == 'E-commerce' ? 'selected' : '' }}>E-commerce</option>
                                            <option value="Brick & Mortar Stores" {{ $user->company_category == 'Brick & Mortar Stores' ? 'selected' : '' }}>Brick & Mortar Stores</option>
                                            <option value="Wholesale" {{ $user->company_category == 'Wholesale' ? 'selected' : '' }}>Wholesale</option>
                                            <option value="Luxury Goods" {{ $user->company_category == 'Luxury Goods' ? 'selected' : '' }}>Luxury Goods</option>
                                        </optgroup>

                                        <optgroup label="6. Real Estate">
                                            <option value="Residential" {{ $user->company_category == 'Residential' ? 'selected' : '' }}>Residential</option>
                                            <option value="Commercial" {{ $user->company_category == 'Commercial' ? 'selected' : '' }}>Commercial</option>
                                            <option value="REITs" {{ $user->company_category == 'REITs' ? 'selected' : '' }}>Real Estate Investment Trusts (REITs)</option>
                                            <option value="Property Management" {{ $user->company_category == 'Property Management' ? 'selected' : '' }}>Property Management</option>
                                        </optgroup>

                                        <optgroup label="7. Energy">
                                            <option value="Oil & Gas" {{ $user->company_category == 'Oil & Gas' ? 'selected' : '' }}>Oil & Gas</option>
                                            <option value="Renewable Energy" {{ $user->company_category == 'Renewable Energy' ? 'selected' : '' }}>Renewable Energy (Solar, Wind, etc.)</option>
                                            <option value="Utilities" {{ $user->company_category == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                                            <option value="Energy Equipment" {{ $user->company_category == 'Energy Equipment' ? 'selected' : '' }}>Energy Equipment & Services</option>
                                        </optgroup>

                                        <optgroup label="8. Industrials">
                                            <option value="Manufacturing" {{ $user->company_category == 'Manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                                            <option value="Construction" {{ $user->company_category == 'Construction' ? 'selected' : '' }}>Construction</option>
                                            <option value="Aerospace & Defense" {{ $user->company_category == 'Aerospace & Defense' ? 'selected' : '' }}>Aerospace & Defense</option>
                                            <option value="Machinery" {{ $user->company_category == 'Machinery' ? 'selected' : '' }}>Machinery</option>
                                            <option value="Logistics" {{ $user->company_category == 'Logistics' ? 'selected' : '' }}>Logistics & Supply Chain</option>
                                        </optgroup>

                                        <optgroup label="9. Telecommunications">
                                            <option value="Internet Providers" {{ $user->company_category == 'Internet Providers' ? 'selected' : '' }}>Internet Providers</option>
                                            <option value="Mobile Networks" {{ $user->company_category == 'Mobile Networks' ? 'selected' : '' }}>Mobile Networks</option>
                                            <option value="Satellite Communications" {{ $user->company_category == 'Satellite Communications' ? 'selected' : '' }}>Satellite Communications</option>
                                        </optgroup>

                                        <optgroup label="10. Media & Entertainment">
                                            <option value="Film & Television" {{ $user->company_category == 'Film & Television' ? 'selected' : '' }}>Film & Television</option>
                                            <option value="Gaming" {{ $user->company_category == 'Gaming' ? 'selected' : '' }}>Gaming</option>
                                            <option value="Music" {{ $user->company_category == 'Music' ? 'selected' : '' }}>Music</option>
                                            <option value="Publishing" {{ $user->company_category == 'Publishing' ? 'selected' : '' }}>Publishing</option>
                                            <option value="Advertising & Marketing" {{ $user->company_category == 'Advertising & Marketing' ? 'selected' : '' }}>Advertising & Marketing</option>
                                        </optgroup>

                                        <optgroup label="11. Education">
                                            <option value="K-12" {{ $user->company_category == 'K-12' ? 'selected' : '' }}>K-12</option>
                                            <option value="Higher Education" {{ $user->company_category == 'Higher Education' ? 'selected' : '' }}>Higher Education</option>
                                            <option value="EdTech" {{ $user->company_category == 'EdTech' ? 'selected' : '' }}>EdTech</option>
                                            <option value="Corporate Training" {{ $user->company_category == 'Corporate Training' ? 'selected' : '' }}>Corporate Training</option>
                                        </optgroup>

                                        <optgroup label="12. Transportation">
                                            <option value="Airlines" {{ $user->company_category == 'Airlines' ? 'selected' : '' }}>Airlines</option>
                                            <option value="Shipping" {{ $user->company_category == 'Shipping' ? 'selected' : '' }}>Shipping</option>
                                            <option value="Ride-sharing" {{ $user->company_category == 'Ride-sharing' ? 'selected' : '' }}>Ride-sharing</option>
                                            <option value="Railways" {{ $user->company_category == 'Railways' ? 'selected' : '' }}>Railways</option>
                                            <option value="Automotive" {{ $user->company_category == 'Automotive' ? 'selected' : '' }}>Automotive</option>
                                        </optgroup>

                                        <optgroup label="13. Hospitality & Travel">
                                            <option value="Hotels & Resorts" {{ $user->company_category == 'Hotels & Resorts' ? 'selected' : '' }}>Hotels & Resorts</option>
                                            <option value="Restaurants & Bars" {{ $user->company_category == 'Restaurants & Bars' ? 'selected' : '' }}>Restaurants & Bars</option>
                                            <option value="Travel Agencies" {{ $user->company_category == 'Travel Agencies' ? 'selected' : '' }}>Travel Agencies</option>
                                            <option value="Tourism Services" {{ $user->company_category == 'Tourism Services' ? 'selected' : '' }}>Tourism Services</option>
                                        </optgroup>

                                        <optgroup label="14. Agriculture & Food Production">
                                            <option value="Farming" {{ $user->company_category == 'Farming' ? 'selected' : '' }}>Farming</option>
                                            <option value="Food Processing" {{ $user->company_category == 'Food Processing' ? 'selected' : '' }}>Food Processing</option>
                                            <option value="Agricultural Equipment" {{ $user->company_category == 'Agricultural Equipment' ? 'selected' : '' }}>Agricultural Equipment</option>
                                            <option value="AgriTech" {{ $user->company_category == 'AgriTech' ? 'selected' : '' }}>AgriTech</option>
                                        </optgroup>

                                        <optgroup label="15. Non-Profit / Social Impact">
                                            <option value="Charities" {{ $user->company_category == 'Charities' ? 'selected' : '' }}>Charities</option>
                                            <option value="NGOs" {{ $user->company_category == 'NGOs' ? 'selected' : '' }}>NGOs</option>
                                            <option value="Foundations" {{ $user->company_category == 'Foundations' ? 'selected' : '' }}>Foundations</option>
                                            <option value="Social Enterprises" {{ $user->company_category == 'Social Enterprises' ? 'selected' : '' }}>Social Enterprises</option>
                                        </optgroup>
                                    </select>

                                </div>

                                <div class="mb-3">
                                    <label for="about_company" class="form-label">
                                        {{ localize('About Company') }}<span class="text-danger">*</span>
                                    </label>

                                    <textarea class="form-control" id="about_company" name="about_company" rows="5" required>{{ old('about_company', $user->about_company ?? '') }}</textarea>
                                </div>


                            @endif

                            </div>

                        </div>

                        <!--basic information end-->







                        <!-- submit button -->

                        <div class="row">

                            <div class="col-12">

                                <div class="mb-3">

                                    <button class="btn btn-primary" type="submit">

                                        <i data-feather="save" class="me-1"></i> {{ localize('Save Changes') }}

                                    </button>

                                </div>

                            </div>

                        </div>

                        <!-- submit button end -->



                    </form>

                </div>



                <!--right sidebar-->

                <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2">

                    <div class="card tt-sticky-sidebar d-none d-xl-block">

                        <div class="card-body">

                            <h5 class="mb-4">{{ localize('User Information') }}</h5>

                            <div class="tt-vertical-step">

                                <ul class="list-unstyled">

                                    <li>

                                        <a href="#section-1" class="active">{{ localize('Basic Information') }}</a>

                                    </li>

                                </ul>

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

        "use strict";



        // runs when the document is ready --> for media files

        $(document).ready(function() {

            getChosenFilesCount();

            showSelectedFilePreviewOnLoad();

        });

    </script>

@endsection

