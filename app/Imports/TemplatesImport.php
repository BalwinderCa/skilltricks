<?php

namespace App\Imports;

use App\Models\Template;
use App\Models\TemplateGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Str;

class TemplatesImport implements ToCollection, WithHeadingRow, WithValidation, ToModel
{
    private $rows = 0;

    public function model(array $row)
    {
        ++$this->rows;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function rules(): array
    {
        return [];
    }

    # import here
    public function collection(Collection $rows)
    {

        $groupNameArray = array_column($rows->toArray(), 'group_name');
        $groupNameNullIndex = array_search('', $groupNameArray);

        foreach ($rows as $key => $row) {
            if ($key == $groupNameNullIndex) {
                break; // break where group name is null
            }

            if (!empty($row['group_name'])) {
                # template group
                $dataGroup = [
                    'name' => $row['group_name'],
                    'slug' => Str::slug($row['group_name']),
                    'icon' => $row['group_icon'],
                ];

                $existingGroup = TemplateGroup::where('slug', $dataGroup['slug'])->first();
                if (is_null($existingGroup)) {
                    $group = TemplateGroup::create($dataGroup);
                    $groupId = $group->id;
                } else {
                    $groupId = $existingGroup->id;
                }

                if (!is_null($row['fields'])) {
                    $fields = str_replace(
                        array("\r\n", "\n", "\r"),
                        '',
                        $row['fields']
                    );
                }

                # templates
                $slug = Str::slug($row['name']);

                $data = [
                    'template_group_id' => $groupId,
                    'name'              => $row['name'],
                    'slug'              => $slug,
                    'code'              => $slug,
                    'description'       => $row['description'],
                    'fields'            => !is_null($row['fields']) ? json_encode($fields) : null,
                    'icon'              => !is_null($row['icon']) ? $row['icon'] : null
                ];

                $existingTemplate = Template::withTrashed()->where('slug', $data['slug'])->where('template_group_id', $groupId)->first();
                if (is_null($existingTemplate)) {
                    if ($slug == "instagram-hashtag") {
                        $instagramTemplate = Template::withTrashed()->where('slug', 'instagram-hashtag')->first();
                        if (!is_null($instagramTemplate)) {
                            $instagramTemplate->update($data);
                        }
                    } else {
                        Template::create($data);
                    }
                } else {
                    $existingTemplate->update($data);
                }
            }
        }
    }
}
