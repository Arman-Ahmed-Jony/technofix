<?php

namespace App\Filament\Resources\ProductQuestions\Pages;

use App\Enums\QuestionStatus;
use App\Filament\Actions\HistoryAction;
use App\Filament\Resources\ProductQuestions\ProductQuestionResource;
use App\Models\ProductQuestion;
use App\Services\ProductFeedback;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProductQuestion extends EditRecord
{
    protected static string $resource = ProductQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [HistoryAction::make(), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['publish'] = $this->record->status !== QuestionStatus::Hidden;

        return $data;
    }

    /** @param  ProductQuestion  $record */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $feedback = app(ProductFeedback::class);
        $answer = trim((string) ($data['answer'] ?? ''));

        if ($data['publish'] ?? false) {
            $feedback->answer($record, $answer, auth('web')->user());
        } else {
            $record->update(['answer' => $answer !== '' ? $answer : $record->answer]);
            $feedback->hideQuestion($record);
        }

        return $record->refresh();
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
