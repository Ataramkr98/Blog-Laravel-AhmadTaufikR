<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\CourseExamQuestion;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncCourseContent
{
    /**
     * @param  array<int, array<string, mixed>>  $modules
     * @param  array<int, array<string, mixed>>  $questions
     */
    public function handle(Course $course, array $modules, array $questions, bool $prune = true): void
    {
        DB::transaction(function () use ($course, $modules, $questions, $prune): void {
            $keptModules = [];

            foreach ($modules as $moduleIndex => $data) {
                $module = isset($data['id'])
                    ? $course->modules()->find($data['id'])
                    : ($prune ? null : $course->modules()->where('title', $data['title'])->first());

                if (isset($data['id']) && ! $module) {
                    throw ValidationException::withMessages(['modules' => 'Modul tidak termasuk dalam kursus ini.']);
                }

                $module ??= new CourseModule(['course_id' => $course->id]);
                if ($prune || ! $module->exists) {
                    $module->fill([
                        'title' => $data['title'],
                        'description' => $data['description'] ?? null,
                        'sort_order' => $moduleIndex + 1,
                    ])->save();
                }
                $keptModules[] = $module->id;
                $keptLessons = [];

                foreach ($data['lessons'] as $lessonIndex => $lessonData) {
                    $lesson = isset($lessonData['id'])
                        ? $module->lessons()->find($lessonData['id'])
                        : ($prune ? null : $module->lessons()->where('article_id', $lessonData['article_id'])->first());

                    if (isset($lessonData['id']) && ! $lesson) {
                        throw ValidationException::withMessages(['modules' => 'Lesson tidak termasuk dalam modul ini.']);
                    }

                    if ($prune && $lesson && $lesson->article_id !== (int) $lessonData['article_id'] && $lesson->progress()->exists()) {
                        throw ValidationException::withMessages(['modules' => 'Materi lesson yang memiliki progres tidak dapat diganti. Tambahkan lesson baru.']);
                    }

                    $lesson ??= new CourseLesson(['course_module_id' => $module->id]);
                    if ($prune || ! $lesson->exists) {
                        $lesson->fill([
                            'article_id' => $lessonData['article_id'],
                            'title' => $lessonData['title'] ?? null,
                            'is_required' => $lessonData['is_required'] ?? true,
                            'sort_order' => $lessonIndex + 1,
                        ])->save();
                    }
                    $keptLessons[] = $lesson->id;
                }

                if ($prune) {
                    $removedLessons = $module->lessons()->whereNotIn('id', $keptLessons);
                    if ((clone $removedLessons)->whereHas('progress')->exists()) {
                        throw ValidationException::withMessages(['modules' => 'Lesson yang memiliki progres siswa tidak dapat dihapus.']);
                    }
                    $removedLessons->delete();
                }
            }

            if ($prune) {
                $removedModules = $course->modules()->whereNotIn('id', $keptModules);
                if ((clone $removedModules)->whereHas('lessons.progress')->exists()) {
                    throw ValidationException::withMessages(['modules' => 'Modul yang memiliki progres siswa tidak dapat dihapus.']);
                }
                $removedModules->delete();
            }

            $keptQuestions = [];
            foreach ($questions as $questionIndex => $data) {
                $options = array_values($data['options']);
                $correctIndex = (int) $data['correct_option_index'];
                if ($correctIndex < 0 || ! array_key_exists($correctIndex, $options)) {
                    throw ValidationException::withMessages([
                        "examQuestions.{$questionIndex}.correct_option_index" => 'Jawaban benar tidak tersedia pada daftar opsi.',
                    ]);
                }

                $question = isset($data['id'])
                    ? $course->examQuestions()->find($data['id'])
                    : ($prune ? null : $course->examQuestions()->where('question', $data['question'])->first());

                if (isset($data['id']) && ! $question) {
                    throw ValidationException::withMessages(['examQuestions' => 'Soal tidak termasuk dalam kursus ini.']);
                }

                $question ??= new CourseExamQuestion(['course_id' => $course->id]);
                if ($prune || ! $question->exists) {
                    $question->fill([
                        'question' => $data['question'],
                        'options' => $options,
                        'correct_option_index' => $correctIndex,
                        'explanation' => $data['explanation'] ?? null,
                        'sort_order' => $questionIndex + 1,
                    ])->save();
                }
                $keptQuestions[] = $question->id;
            }

            if ($prune) {
                $course->examQuestions()->whereNotIn('id', $keptQuestions)->delete();
            }
        });
    }
}
