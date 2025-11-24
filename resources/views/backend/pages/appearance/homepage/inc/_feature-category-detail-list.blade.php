<table class="table tt-footable" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center" width="7%">{{ localize('S/L') }}</th>
            <th>{{ localize('Image') }}</th>
            <th>{{ localize('Category') }}</th>
            <th>{{ localize('Name') }}</th>
            <th>{{ localize('Short Description') }}</th>
            <th>{{ localize('Icon') }}</th>
            <th>{{ localize('Active') }}</th>
            <th data-breakpoints="xs sm" class="text-end">
                {{ localize('Action') }}
            </th>
        </tr>
    </thead>
    <tbody>

        @foreach ($featureCategoryDetails as $key => $detail)
            <tr>
                <td class="text-center align-middle">
                    {{ $key + 1 }}
                </td>
                <td>
                    <div class="avatar avatar-md flex-shrink-0">
                        <img class="rounded" src="{{uploadedAsset($detail->image)}}" alt="avatar">
                </div>
                   
                </td>
                <td>{{$detail->category->name}}</td>
                <td class="align-middle">
                    <h6 class="fs-sm mb-0">
                        {{ $detail->title }}</h6>
                </td>
                <td>{{ $detail->short_description }}</td>
                <td>{{ $detail->icon }}</td>
                <td>
                    <x-status-change :modelid="$detail->id" :table="$detail->getTable()" :status="$detail->is_active" />
                </td>



                <td class="text-end align-middle">
                    <div class="dropdown tt-tb-dropdown">
                        <button type="button" class="btn p-0" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow">

                            <a class="dropdown-item"
                                href="{{ route('admin.appearance.homepage.edit-feature-category-detail', ['id' => $detail->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}">
                                <i data-feather="edit-3" class="me-2"></i>{{ localize('Edit') }}
                            </a>

                            <a href="#" class="dropdown-item confirm-delete"
                                data-href="{{ route('admin.appearance.homepage.delete-feature-category-detail', $detail->id) }}"
                                title="{{ localize('Delete') }}">
                                <i data-feather="trash-2" class="me-2"></i>
                                {{ localize('Delete') }}
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
