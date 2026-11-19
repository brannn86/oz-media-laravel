<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Filament\Support\StationFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(2)
                            ->schema([
                                Section::make()
                                    ->schema([
                                        ...StationFields::titleAndSlug(),
                                        Textarea::make('excerpt')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->helperText('Short summary shown on news cards.'),
                                        RichEditor::make('body')
                                            ->required()
                                            ->fileAttachmentsDirectory('posts/attachments')
                                            ->extraInputAttributes(['style' => 'min-height: 24rem']),
                                    ]),
                                Section::make('SEO')
                                    ->description('Optional. Falls back to the title and excerpt.')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->maxLength(70)
                                            ->placeholder(fn (Get $get): ?string => $get('title')),
                                        Textarea::make('meta_description')
                                            ->rows(2)
                                            ->maxLength(160)
                                            ->placeholder(fn (Get $get): ?string => $get('excerpt')),
                                    ]),
                            ]),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Section::make('Publishing')
                                    ->schema([
                                        ToggleButtons::make('status')
                                            ->options(PostStatus::class)
                                            ->default(PostStatus::Draft)
                                            ->inline()
                                            ->required()
                                            ->live(),
                                        DateTimePicker::make('published_at')
                                            ->label('Publish at (WIB)')
                                            ->seconds(false)
                                            ->native(false)
                                            ->helperText('Leave empty to publish now. A future date schedules the article.')
                                            ->visible(fn (Get $get): bool => self::isPublished($get('status'))),
                                        StationFields::select(),
                                        Select::make('author_id')
                                            ->label('Author')
                                            ->relationship('author', 'name')
                                            ->default(fn (): ?int => auth()->user()?->id)
                                            ->searchable()
                                            ->preload(),
                                    ]),
                                Section::make('Featured image')
                                    ->schema([
                                        FileUpload::make('featured_image')
                                            ->hiddenLabel()
                                            ->image()
                                            ->imageEditor()
                                            ->imageAspectRatio('16:9')
                                            ->directory('posts')
                                            ->maxSize(4096),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private static function isPublished(mixed $status): bool
    {
        return ($status instanceof PostStatus ? $status : PostStatus::tryFrom((string) $status)) === PostStatus::Published;
    }
}
