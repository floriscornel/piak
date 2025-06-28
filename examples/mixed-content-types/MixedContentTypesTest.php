<?php

require_once __DIR__.'/expected/ContentRequest.php';
require_once __DIR__.'/expected/RichContent.php';
require_once __DIR__.'/expected/MetadataObject.php';
require_once __DIR__.'/expected/AuthorDetail.php';
require_once __DIR__.'/expected/AttachmentDetail.php';
require_once __DIR__.'/expected/LocalizedDescription.php';
require_once __DIR__.'/expected/TagObject.php';
require_once __DIR__.'/expected/ApiResponse.php';
require_once __DIR__.'/expected/SuccessResponse.php';
require_once __DIR__.'/expected/ErrorResponse.php';
require_once __DIR__.'/expected/ErrorDetail.php';

use PHPUnit\Framework\TestCase;

class MixedContentTypesTest extends TestCase
{
    public function test_rich_content_from_array(): void
    {
        $data = [
            'type' => 'html',
            'data' => '<p>Hello World</p>',
            'encoding' => 'utf8',
            'size' => 18,
            'checksum' => 'abc123',
        ];

        $richContent = RichContent::fromArray($data);

        $this->assertEquals('html', $richContent->type);
        $this->assertEquals('<p>Hello World</p>', $richContent->data);
        $this->assertEquals('utf8', $richContent->encoding);
        $this->assertEquals(18, $richContent->size);
        $this->assertEquals('abc123', $richContent->checksum);
    }

    public function test_rich_content_minimal_data(): void
    {
        $data = [
            'type' => 'markdown',
            'data' => '# Hello World',
        ];

        $richContent = RichContent::fromArray($data);

        $this->assertEquals('markdown', $richContent->type);
        $this->assertEquals('# Hello World', $richContent->data);
        $this->assertNull($richContent->encoding);
        $this->assertNull($richContent->size);
        $this->assertNull($richContent->checksum);
    }

    public function test_author_detail_from_array(): void
    {
        $data = [
            'id' => 'user123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'editor',
        ];

        $author = AuthorDetail::fromArray($data);

        $this->assertEquals('user123', $author->id);
        $this->assertEquals('John Doe', $author->name);
        $this->assertEquals('john@example.com', $author->email);
        $this->assertEquals('editor', $author->role);
    }

    public function test_metadata_object_with_string_author(): void
    {
        $data = [
            'source' => 'import',
            'created' => '2023-01-15T10:30:00Z',
            'author' => 'John Doe',
            'keywords' => ['important', 'urgent'],
            'priority' => 5,
        ];

        $metadata = MetadataObject::fromArray($data);

        $this->assertEquals('import', $metadata->source);
        $this->assertEquals('2023-01-15T10:30:00Z', $metadata->created);
        $this->assertTrue($metadata->isAuthorString());
        $this->assertEquals('John Doe', $metadata->getAuthorString());
        $this->assertEquals(['important', 'urgent'], $metadata->keywords);
        $this->assertEquals(5, $metadata->priority);
    }

    public function test_metadata_object_with_author_detail(): void
    {
        $data = [
            'source' => 'api',
            'created' => '2023-01-15T10:30:00Z',
            'author' => [
                'id' => 'user123',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'admin',
            ],
            'keywords' => ['test'],
            'priority' => 10,
        ];

        $metadata = MetadataObject::fromArray($data);

        $this->assertEquals('api', $metadata->source);
        $this->assertTrue($metadata->isAuthorObject());
        $author = $metadata->getAuthorObject();
        $this->assertEquals('user123', $author->id);
        $this->assertEquals('John Doe', $author->name);
        $this->assertEquals('admin', $author->role);
    }

    public function test_localized_description_from_array(): void
    {
        $data = [
            'en' => 'English description',
            'es' => 'Descripción en español',
            'fr' => 'Description française',
            'default' => 'Default description',
        ];

        $description = LocalizedDescription::fromArray($data);

        $this->assertEquals('English description', $description->en);
        $this->assertEquals('Descripción en español', $description->es);
        $this->assertEquals('Description française', $description->fr);
        $this->assertEquals('Default description', $description->default);
    }

    public function test_attachment_detail_with_string_description(): void
    {
        $data = [
            'url' => 'https://example.com/file.pdf',
            'filename' => 'document.pdf',
            'size' => 1024,
            'mimeType' => 'application/pdf',
            'description' => 'Important document',
        ];

        $attachment = AttachmentDetail::fromArray($data);

        $this->assertEquals('https://example.com/file.pdf', $attachment->url);
        $this->assertEquals('document.pdf', $attachment->filename);
        $this->assertEquals(1024, $attachment->size);
        $this->assertEquals('application/pdf', $attachment->mimeType);
        $this->assertTrue($attachment->isDescriptionString());
        $this->assertEquals('Important document', $attachment->getDescriptionString());
    }

    public function test_attachment_detail_with_localized_description(): void
    {
        $data = [
            'url' => 'https://example.com/file.pdf',
            'filename' => 'document.pdf',
            'description' => [
                'en' => 'English description',
                'es' => 'Descripción en español',
            ],
        ];

        $attachment = AttachmentDetail::fromArray($data);

        $this->assertTrue($attachment->isDescriptionObject());
        $description = $attachment->getDescriptionObject();
        $this->assertEquals('English description', $description->en);
        $this->assertEquals('Descripción en español', $description->es);
    }

    public function test_tag_object_from_array(): void
    {
        $data = [
            'name' => 'urgent',
            'category' => 'priority',
            'color' => '#ff0000',
            'weight' => 0.8,
        ];

        $tag = TagObject::fromArray($data);

        $this->assertEquals('urgent', $tag->name);
        $this->assertEquals('priority', $tag->category);
        $this->assertEquals('#ff0000', $tag->color);
        $this->assertEquals(0.8, $tag->weight);
    }

    public function test_content_request_with_string_types(): void
    {
        $data = [
            'title' => 'Test Content',
            'body' => 'Simple text body',
            'metadata' => 'Basic metadata string',
            'attachments' => ['file1.txt', 'file2.txt'],
            'tags' => ['tag1', 'tag2'],
        ];

        $request = ContentRequest::fromArray($data);

        $this->assertEquals('Test Content', $request->title);
        $this->assertTrue($request->isBodyString());
        $this->assertEquals('Simple text body', $request->getBodyString());
        $this->assertTrue($request->isMetadataString());
        $this->assertEquals('Basic metadata string', $request->getMetadataString());
        $this->assertEquals(['file1.txt', 'file2.txt'], $request->attachments);
        $this->assertEquals(['tag1', 'tag2'], $request->tags);
    }

    public function test_content_request_with_object_types(): void
    {
        $data = [
            'title' => 'Rich Content',
            'body' => [
                'type' => 'html',
                'data' => '<h1>Rich HTML Content</h1>',
            ],
            'metadata' => [
                'source' => 'editor',
                'author' => 'Jane Doe',
                'priority' => 8,
            ],
            'attachments' => [
                [
                    'url' => 'https://example.com/file.pdf',
                    'filename' => 'document.pdf',
                ],
            ],
            'tags' => [
                [
                    'name' => 'important',
                    'category' => 'priority',
                    'weight' => 0.9,
                ],
            ],
        ];

        $request = ContentRequest::fromArray($data);

        $this->assertEquals('Rich Content', $request->title);
        $this->assertTrue($request->isBodyObject());
        $body = $request->getBodyObject();
        $this->assertEquals('html', $body->type);
        $this->assertEquals('<h1>Rich HTML Content</h1>', $body->data);
        $this->assertTrue($request->isMetadataObject());
        $metadata = $request->getMetadataObject();
        $this->assertEquals('editor', $metadata->source);
        $this->assertEquals(8, $metadata->priority);

        $this->assertCount(1, $request->attachments);
        $this->assertInstanceOf(AttachmentDetail::class, $request->attachments[0]);
        $this->assertEquals('document.pdf', $request->attachments[0]->filename);

        $this->assertCount(1, $request->tags);
        $this->assertInstanceOf(TagObject::class, $request->tags[0]);
        $this->assertEquals('important', $request->tags[0]->name);
        $this->assertEquals(0.9, $request->tags[0]->weight);
    }

    public function test_error_detail_from_array(): void
    {
        $data = [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Invalid input data',
            'field' => 'email',
            'value' => 'invalid-email',
        ];

        $error = ErrorDetail::fromArray($data);

        $this->assertEquals('VALIDATION_ERROR', $error->code);
        $this->assertEquals('Invalid input data', $error->message);
        $this->assertEquals('email', $error->field);
        $this->assertEquals('invalid-email', $error->value);
    }

    public function test_api_response_with_success_response(): void
    {
        $data = [
            'success' => true,
            'data' => ['id' => '123', 'name' => 'Test'],
            'meta' => [
                'timestamp' => '2023-01-15T10:30:00Z',
                'version' => '1.0',
            ],
        ];

        $response = ApiResponse::fromArray($data);

        $this->assertTrue($response->isSuccessResponse());
        $successResponse = $response->getSuccessResponse();
        $this->assertTrue($successResponse->success);
        $this->assertEquals(['id' => '123', 'name' => 'Test'], $successResponse->data);
    }

    public function test_api_response_with_error_response(): void
    {
        $data = [
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Resource not found',
            ],
            'meta' => [
                'timestamp' => '2023-01-15T10:30:00Z',
                'requestId' => 'req-123',
            ],
        ];

        $response = ApiResponse::fromArray($data);

        $this->assertTrue($response->isErrorResponse());
        $errorResponse = $response->getErrorResponse();
        $this->assertFalse($errorResponse->success);
        $this->assertInstanceOf(ErrorDetail::class, $errorResponse->error);
        $errorDetail = $errorResponse->error;
        $this->assertEquals('NOT_FOUND', $errorDetail->code);
        $this->assertEquals('Resource not found', $errorDetail->message);
    }

    public function test_complex_mixed_content_scenario(): void
    {
        $data = [
            'title' => 'Complex Mixed Content',
            'body' => [
                'type' => 'markdown',
                'data' => '# Complex Content\n\nWith **mixed** types.',
                'size' => 42,
            ],
            'metadata' => [
                'source' => 'api',
                'author' => [
                    'id' => 'user456',
                    'name' => 'Complex Author',
                    'email' => 'complex@example.com',
                ],
                'keywords' => ['complex', 'mixed', 'types'],
                'priority' => 7,
            ],
            'attachments' => [
                'simple-file.txt',
                [
                    'url' => 'https://example.com/complex.pdf',
                    'filename' => 'complex.pdf',
                    'size' => 2048,
                    'description' => [
                        'en' => 'Complex document',
                        'fr' => 'Document complexe',
                    ],
                ],
            ],
            'tags' => [
                'simple-tag',
                [
                    'name' => 'complex-tag',
                    'category' => 'system',
                    'color' => '#00ff00',
                    'weight' => 0.75,
                ],
            ],
        ];

        $request = ContentRequest::fromArray($data);

        // Validate body as RichContent
        $this->assertTrue($request->isBodyObject());
        $body = $request->getBodyObject();
        $this->assertEquals('markdown', $body->type);
        $this->assertEquals(42, $body->size);

        // Validate metadata as MetadataObject with AuthorDetail
        $this->assertTrue($request->isMetadataObject());
        $metadata = $request->getMetadataObject();
        $this->assertTrue($metadata->isAuthorObject());
        $author = $metadata->getAuthorObject();
        $this->assertEquals('user456', $author->id);
        $this->assertEquals(['complex', 'mixed', 'types'], $metadata->keywords);

        // Validate mixed attachments (string + AttachmentDetail)
        $this->assertCount(2, $request->attachments);
        $this->assertEquals('simple-file.txt', $request->attachments[0]);
        $this->assertInstanceOf(AttachmentDetail::class, $request->attachments[1]);
        $attachment = $request->attachments[1];
        $this->assertEquals('complex.pdf', $attachment->filename);
        $this->assertTrue($attachment->isDescriptionObject());

        // Validate mixed tags (string + TagObject)
        $this->assertCount(2, $request->tags);
        $this->assertEquals('simple-tag', $request->tags[0]);
        $this->assertInstanceOf(TagObject::class, $request->tags[1]);
        $tag = $request->tags[1];
        $this->assertEquals('complex-tag', $tag->name);
        $this->assertEquals(0.75, $tag->weight);
    }

    public function test_empty_and_null_values(): void
    {
        $data = [
            'title' => 'Minimal Content',
            'body' => '',
        ];

        $request = ContentRequest::fromArray($data);

        $this->assertEquals('Minimal Content', $request->title);
        $this->assertTrue($request->isBodyString());
        $this->assertEquals('', $request->getBodyString());
        $this->assertNull($request->metadata);
        $this->assertEquals([], $request->attachments);
        $this->assertEquals([], $request->tags);
    }
}
