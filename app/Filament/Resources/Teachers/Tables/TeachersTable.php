<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Tables;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('اسم المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('centers.name')
                    ->label('المراكز')
                    ->listWithLineBreaks()
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('qualification')
                    ->label('المؤهل')
                    ->toggleable(),

                TextColumn::make('hire_date')
                    ->label('تاريخ التعيين')
                    ->date()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make()
                    ->visible(
                        fn (Teacher $record): bool =>
                            TeacherResource::canEdit($record)
                    ),

                DeleteAction::make()
                    ->visible(
                        fn (Teacher $record): bool =>
                            TeacherResource::canDelete($record)
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(
                            fn (): bool =>
                                Gate::allows('teachers.delete')
                        )
                        ->authorizeIndividualRecords(
                            fn (Model $record): bool =>
                                $record instanceof Teacher
                                && TeacherResource::canDelete($record)
                        ),
                ]),
            ]);
    }
}