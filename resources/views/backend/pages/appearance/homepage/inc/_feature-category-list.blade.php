<table class="table tt-footable" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center" width="7%">{{ localize('S/L') }}</th>
            <th>{{ localize('Name') }}</th>
            <th>{{ localize('Icon') }}</th>
            <th>{{ localize('Active') }}</th>
            <th data-breakpoints="xs sm" class="text-end">
                {{ localize('Action') }}
            </th>
        </tr>
    </thead>
    <tbody>

        @foreach ($featureCategories as $key => $category)
            <tr>
                <td class="text-center align-middle">
                    {{ $key + 1 }}
                </td>

                <td class="align-middle">
                    <h6 class="fs-sm mb-0">
                        {{ $category->name }}</h6>                                                  
                </td>
                <td class="align-middle">
                    {{$category->icon}}                                                 
                </td>
                <td>
                    <x-status-change :modelid="$category->id" :table="$category->getTable()"
                        :status="$category->is_active" />                                                
                </td>



                <td class="text-end align-middle">
                    <div class="dropdown tt-tb-dropdown">
                        <button type="button" class="btn p-0" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow">

                            <a class="dropdown-item"
                                href="{{ route('admin.appearance.homepage.edit-feature-category', ['id' => $category->id, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}">
                                <i data-feather="edit-3"
                                    class="me-2"></i>{{ localize('Edit') }}
                            </a>

                            <a href="#" class="dropdown-item confirm-delete"
                                data-href="{{ route('admin.appearance.homepage.delete-feature-category', $category->id) }}"
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