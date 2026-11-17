<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSetting;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormController extends Controller
{
    
public function __construct()
    {
        $this->authorizeResource(Form::class, 'form');
    }

public function preview(Form $form)
    {
        // بارگذاری تنظیمات فرم
        $setting = $form->setting ?? new FormSetting(['form_id' => $form->id]);

        // بارگذاری فیلدها با ترتیب نمایش
        $fields = $form->fields()->orderBy('order')->get();

        return view('back.forms.preview', compact('form', 'setting', 'fields'));
    }

public function saveSettings(Request $request, Form $form)
    {
        $settings = $request->validate([
            'form_position' => 'required|in:top,bottom',
            'form_width' => 'required|in:full,half,third',
            'form_alignment' => 'required|in:center,right,left',
            'form_class' => 'nullable|string',
            'custom_css' => 'nullable|string',
            'default_column_class' => 'nullable|string',
            'field_settings' => 'nullable|array',
        ]);

        $setting = $form->setting ?? new FormSetting(['form_id' => $form->id]);
        $setting->fill($settings);
        $setting->save();

        return response()->json(['success' => true]);
    }

public function updateFieldsDisplay(Request $request, Form $form)
    {
        $fieldsOrder = $request->order;

        foreach ($fieldsOrder as $index => $fieldData) {
            $field = FormField::find($fieldData['id']);
            if ($field && $field->form_id == $form->id) {
                $field->update([
                    'order' => $index,
                    'column_class' => $fieldData['column_class'] ?? $field->column_class,
                    'show_label' => $fieldData['show_label'] ?? $field->show_label,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}
