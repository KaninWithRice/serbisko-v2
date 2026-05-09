<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\CustomForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    public function index()
    {
        $activeSY = \App\Models\Student::activeYear();

        $sections = Section::orderBy('academic_year', 'desc')

            ->orderBy('grade_level', 'asc')
            ->orderBy('name', 'asc')
            ->get();
            
        $academicYears = Section::distinct()->pluck('academic_year')->toArray();
        if (!in_array($activeSY, $academicYears)) {
            $academicYears[] = $activeSY;
        }
        sort($academicYears);

        return view('admin.sections.index', compact('sections', 'academicYears', 'activeSY'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|string',
            'grade_level' => 'required|in:Grade 11,Grade 12',
            'section_names' => 'required|array',
            'section_names.*' => 'required|string|max:255',
        ]);

        $ay = $request->academic_year;
        $gl = $request->grade_level;
        $names = $request->section_names;

        DB::beginTransaction();
        try {
            foreach ($names as $name) {
                Section::updateOrCreate(
                    [
                        'academic_year' => $ay,
                        'grade_level' => $gl,
                        'name' => trim($name),
                    ]
                );
            }
            DB::commit();
            return back()->with('success', count($names) . ' sections created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating sections: ' . $e->getMessage());
        }
    }

    public function getSections(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|string',
            'grade_level' => 'required|string',
        ]);

        $sections = Section::where('academic_year', $request->academic_year)
            ->where('grade_level', $request->grade_level)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return response()->json($sections);
    }
    
    public function destroy($id)
    {
        $section = Section::findOrFail($id);
        $section->delete();
        return back()->with('success', 'Section deleted successfully.');
    }
}
