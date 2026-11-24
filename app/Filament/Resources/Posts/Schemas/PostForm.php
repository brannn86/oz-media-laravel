<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Filament\Support\StationFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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
                                Section::make('Source')
                                    ->description('Optional. Shown at the end of the article, e.g. when a story comes from a press release or another outlet.')
                                    ->collapsible()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('source_name')
                                            ->label('Source name')
                                            ->maxLength(255)
                                            ->placeholder('e.g. Pemkot Bandung')
                                            ->requiredWith('source_url')
                                            ->validationMessages(['required_with' => 'Name the source the link points to.']),
                                        TextInput::make('source_url')
                                            ->label('Source link')
                                            ->url()
                                            ->rule('url:http,https')
                                            ->maxLength(2048)
                                            ->placeholder('https://'),
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
                                        Select::make('category')
                                            ->options(PostCategory::class)
                                            ->required()
                                            ->searchable()
                                            ->native(false),
                                        Select::make('author_id')
                                            ->label('Author')
                                            ->relationship('author', 'name')
                                            ->default(fn (): ?int => auth()->user()?->id)
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Shown in the byline. Left empty, it becomes whoever publishes the article.'),
                                    ]),
                                Section::make('Featured image')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('featured')
                                            ->collection('featured')
                                            ->hiddenLabel()
                                            ->image()
                                            ->imageEditor()
                                            ->imageAspectRatio('16:9')
                                            ->maxSize(4096),
                                        Textarea::make('image_caption')
                                            ->label('Caption')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->helperText('What is happening in the picture.'),
                                        TextInput::make('image_credit')
                                            ->label('Credit')
                                            ->maxLength(255)
                                            ->placeholder('e.g. OZ Bandung/Raka Pratama')
                                            ->required(fn (Get $get): bool => filled($get('featured')))
                                            ->validationMessages(['required' => 'Every image needs a credit: who took it or where it comes from.']),
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
