<?php

declare(strict_types=1);

require_once 'expected/Category.php';
require_once 'expected/Comment.php';
require_once 'expected/MenuNode.php';

use PHPUnit\Framework\TestCase;

class RecursiveSchemasTest extends TestCase
{
    // ========================================
    // Category Tests (Tree Structure)
    // ========================================

    public function test_category_from_array_minimal(): void
    {
        $json = [
            'id' => 'cat-001',
            'name' => 'Electronics',
        ];

        $category = Category::fromArray($json);

        $this->assertSame('cat-001', $category->id);
        $this->assertSame('Electronics', $category->name);
        $this->assertNull($category->description);
        $this->assertNull($category->parentId);
        $this->assertEmpty($category->childrenIds);
        $this->assertNull($category->depth);
        $this->assertTrue($category->isRoot());
        $this->assertFalse($category->hasChildren());
    }

    public function test_category_from_array_with_parent_object(): void
    {
        $json = [
            'id' => 'cat-002',
            'name' => 'Smartphones',
            'description' => 'Mobile phones and accessories',
            'parent' => [
                'id' => 'cat-001',
                'name' => 'Electronics',
            ],
            'depth' => 1,
        ];

        $category = Category::fromArray($json);

        $this->assertSame('cat-002', $category->id);
        $this->assertSame('Smartphones', $category->name);
        $this->assertSame('Mobile phones and accessories', $category->description);
        $this->assertSame('cat-001', $category->parentId); // Parent object converted to ID
        $this->assertEmpty($category->childrenIds);
        $this->assertSame(1, $category->depth);
        $this->assertFalse($category->isRoot());
        $this->assertFalse($category->hasChildren());
    }

    public function test_category_from_array_with_children_objects(): void
    {
        $json = [
            'id' => 'cat-001',
            'name' => 'Electronics',
            'description' => 'Electronic devices and gadgets',
            'children' => [
                [
                    'id' => 'cat-002',
                    'name' => 'Smartphones',
                ],
                [
                    'id' => 'cat-003',
                    'name' => 'Laptops',
                ],
            ],
            'depth' => 0,
        ];

        $category = Category::fromArray($json);

        $this->assertSame('cat-001', $category->id);
        $this->assertSame('Electronics', $category->name);
        $this->assertNull($category->parentId);
        $this->assertSame(['cat-002', 'cat-003'], $category->childrenIds); // Children objects converted to IDs
        $this->assertSame(0, $category->depth);
        $this->assertTrue($category->isRoot());
        $this->assertTrue($category->hasChildren());
        $this->assertSame(['cat-002', 'cat-003'], $category->getAllChildrenIds());
    }

    public function test_category_from_array_with_id_references(): void
    {
        $json = [
            'id' => 'cat-002',
            'name' => 'Smartphones',
            'parentId' => 'cat-001',
            'childrenIds' => ['cat-004', 'cat-005'],
            'depth' => 1,
        ];

        $category = Category::fromArray($json);

        $this->assertSame('cat-002', $category->id);
        $this->assertSame('Smartphones', $category->name);
        $this->assertSame('cat-001', $category->parentId);
        $this->assertSame(['cat-004', 'cat-005'], $category->childrenIds);
        $this->assertSame(1, $category->depth);
        $this->assertFalse($category->isRoot());
        $this->assertTrue($category->hasChildren());
    }

    public function test_category_recursive_structure_prevention(): void
    {
        // Test complex recursive structure where children contain parent references
        $json = [
            'id' => 'cat-001',
            'name' => 'Root Category',
            'children' => [
                [
                    'id' => 'cat-002',
                    'name' => 'Child 1',
                    'parent' => [
                        'id' => 'cat-001',
                        'name' => 'Root Category',
                        'children' => [
                            ['id' => 'cat-002', 'name' => 'Child 1'],
                            ['id' => 'cat-003', 'name' => 'Child 2'],
                        ],
                    ],
                    'children' => [
                        [
                            'id' => 'cat-004',
                            'name' => 'Grandchild',
                            'parent' => ['id' => 'cat-002'],
                        ],
                    ],
                ],
            ],
        ];

        $category = Category::fromArray($json);

        // Verify recursion is prevented by using IDs only
        $this->assertSame('cat-001', $category->id);
        $this->assertSame(['cat-002'], $category->childrenIds);
        $this->assertNull($category->parentId);
        $this->assertTrue($category->isRoot());
    }

    // ========================================
    // Comment Tests (Threaded Structure)
    // ========================================

    public function test_comment_from_array_minimal(): void
    {
        $json = [
            'id' => 'comment-001',
            'content' => 'Great article!',
            'author' => 'john_doe',
        ];

        $comment = Comment::fromArray($json);

        $this->assertSame('comment-001', $comment->id);
        $this->assertSame('Great article!', $comment->content);
        $this->assertSame('john_doe', $comment->author);
        $this->assertNull($comment->timestamp);
        $this->assertNull($comment->parentId);
        $this->assertEmpty($comment->replyIds);
        $this->assertNull($comment->level);
        $this->assertTrue($comment->isTopLevel());
        $this->assertFalse($comment->hasReplies());
    }

    public function test_comment_from_array_with_parent_object(): void
    {
        $json = [
            'id' => 'comment-002',
            'content' => 'I agree with your point.',
            'author' => 'jane_smith',
            'timestamp' => '2024-01-15T10:30:00Z',
            'parent' => [
                'id' => 'comment-001',
                'content' => 'Great article!',
                'author' => 'john_doe',
            ],
            'level' => 1,
        ];

        $comment = Comment::fromArray($json);

        $this->assertSame('comment-002', $comment->id);
        $this->assertSame('I agree with your point.', $comment->content);
        $this->assertSame('jane_smith', $comment->author);
        $this->assertSame('2024-01-15T10:30:00Z', $comment->timestamp);
        $this->assertSame('comment-001', $comment->parentId); // Parent object converted to ID
        $this->assertEmpty($comment->replyIds);
        $this->assertSame(1, $comment->level);
        $this->assertFalse($comment->isTopLevel());
        $this->assertFalse($comment->hasReplies());
    }

    public function test_comment_from_array_with_replies_objects(): void
    {
        $json = [
            'id' => 'comment-001',
            'content' => 'What do you think about this?',
            'author' => 'alice_wonder',
            'timestamp' => '2024-01-15T09:00:00Z',
            'replies' => [
                [
                    'id' => 'comment-002',
                    'content' => 'Interesting perspective!',
                    'author' => 'bob_builder',
                ],
                [
                    'id' => 'comment-003',
                    'content' => 'I have some concerns...',
                    'author' => 'charlie_chaplin',
                ],
            ],
            'level' => 0,
        ];

        $comment = Comment::fromArray($json);

        $this->assertSame('comment-001', $comment->id);
        $this->assertSame('What do you think about this?', $comment->content);
        $this->assertSame('alice_wonder', $comment->author);
        $this->assertSame('2024-01-15T09:00:00Z', $comment->timestamp);
        $this->assertNull($comment->parentId);
        $this->assertSame(['comment-002', 'comment-003'], $comment->replyIds); // Reply objects converted to IDs
        $this->assertSame(0, $comment->level);
        $this->assertTrue($comment->isTopLevel());
        $this->assertTrue($comment->hasReplies());
        $this->assertSame(['comment-002', 'comment-003'], $comment->getAllReplyIds());
    }

    public function test_comment_from_array_with_id_references(): void
    {
        $json = [
            'id' => 'comment-004',
            'content' => 'Thanks for the clarification.',
            'author' => 'diana_prince',
            'timestamp' => '2024-01-15T11:45:00Z',
            'parentId' => 'comment-001',
            'replyIds' => ['comment-005', 'comment-006'],
            'level' => 2,
        ];

        $comment = Comment::fromArray($json);

        $this->assertSame('comment-004', $comment->id);
        $this->assertSame('Thanks for the clarification.', $comment->content);
        $this->assertSame('diana_prince', $comment->author);
        $this->assertSame('2024-01-15T11:45:00Z', $comment->timestamp);
        $this->assertSame('comment-001', $comment->parentId);
        $this->assertSame(['comment-005', 'comment-006'], $comment->replyIds);
        $this->assertSame(2, $comment->level);
        $this->assertFalse($comment->isTopLevel());
        $this->assertTrue($comment->hasReplies());
    }

    public function test_comment_nested_thread_prevention(): void
    {
        // Test deeply nested comment thread with circular references
        $json = [
            'id' => 'comment-001',
            'content' => 'Root comment',
            'author' => 'user1',
            'replies' => [
                [
                    'id' => 'comment-002',
                    'content' => 'First reply',
                    'author' => 'user2',
                    'parent' => [
                        'id' => 'comment-001',
                        'content' => 'Root comment',
                        'replies' => [
                            ['id' => 'comment-002'],
                            ['id' => 'comment-003'],
                        ],
                    ],
                    'replies' => [
                        [
                            'id' => 'comment-003',
                            'content' => 'Nested reply',
                            'author' => 'user3',
                            'parent' => ['id' => 'comment-002'],
                        ],
                    ],
                ],
            ],
        ];

        $comment = Comment::fromArray($json);

        // Verify recursion is prevented by using IDs only
        $this->assertSame('comment-001', $comment->id);
        $this->assertSame(['comment-002'], $comment->replyIds);
        $this->assertNull($comment->parentId);
        $this->assertTrue($comment->isTopLevel());
    }

    // ========================================
    // MenuNode Tests (Hierarchical Structure)
    // ========================================

    public function test_menu_node_from_array_minimal(): void
    {
        $json = [
            'id' => 'menu-001',
            'label' => 'Home',
        ];

        $menu = MenuNode::fromArray($json);

        $this->assertSame('menu-001', $menu->id);
        $this->assertSame('Home', $menu->label);
        $this->assertNull($menu->href);
        $this->assertNull($menu->icon);
        $this->assertEmpty($menu->children);
        $this->assertFalse($menu->hasChildren());
        $this->assertSame(1, $menu->getDepth());
        $this->assertEmpty($menu->getAllChildrenIds());
    }

    public function test_menu_node_from_array_with_children(): void
    {
        $json = [
            'id' => 'menu-001',
            'label' => 'Products',
            'href' => '/products',
            'icon' => 'shopping-cart',
            'children' => [
                [
                    'id' => 'menu-002',
                    'label' => 'Electronics',
                    'href' => '/products/electronics',
                ],
                [
                    'id' => 'menu-003',
                    'label' => 'Clothing',
                    'href' => '/products/clothing',
                    'children' => [
                        [
                            'id' => 'menu-004',
                            'label' => 'Shirts',
                            'href' => '/products/clothing/shirts',
                        ],
                    ],
                ],
            ],
        ];

        $menu = MenuNode::fromArray($json);

        $this->assertSame('menu-001', $menu->id);
        $this->assertSame('Products', $menu->label);
        $this->assertSame('/products', $menu->href);
        $this->assertSame('shopping-cart', $menu->icon);
        $this->assertTrue($menu->hasChildren());
        $this->assertCount(2, $menu->children);
        $this->assertSame(3, $menu->getDepth()); // Root -> Electronics/Clothing -> Shirts

        // Test first child
        $electronics = $menu->children[0];
        $this->assertSame('menu-002', $electronics->id);
        $this->assertSame('Electronics', $electronics->label);
        $this->assertSame('/products/electronics', $electronics->href);
        $this->assertEmpty($electronics->children);

        // Test second child with nested structure
        $clothing = $menu->children[1];
        $this->assertSame('menu-003', $clothing->id);
        $this->assertSame('Clothing', $clothing->label);
        $this->assertCount(1, $clothing->children);

        // Test nested child
        $shirts = $clothing->children[0];
        $this->assertSame('menu-004', $shirts->id);
        $this->assertSame('Shirts', $shirts->label);
        $this->assertSame('/products/clothing/shirts', $shirts->href);

        // Test flattening
        $flattened = $menu->flatten();
        $this->assertCount(4, $flattened);
        $this->assertArrayHasKey('menu-001', $flattened);
        $this->assertArrayHasKey('menu-002', $flattened);
        $this->assertArrayHasKey('menu-003', $flattened);
        $this->assertArrayHasKey('menu-004', $flattened);

        // Test getAllChildrenIds
        $childrenIds = $menu->getAllChildrenIds();
        $this->assertSame(['menu-002', 'menu-003', 'menu-004'], $childrenIds);
    }

    public function test_menu_node_depth_limiting(): void
    {
        // Create a deep structure that would exceed default max depth
        $json = [
            'id' => 'root',
            'label' => 'Root',
            'children' => [
                [
                    'id' => 'level1',
                    'label' => 'Level 1',
                    'children' => [
                        [
                            'id' => 'level2',
                            'label' => 'Level 2',
                            'children' => [
                                [
                                    'id' => 'level3',
                                    'label' => 'Level 3',
                                    'children' => [
                                        [
                                            'id' => 'level4',
                                            'label' => 'Level 4',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Test with default max depth (should work)
        $menu = MenuNode::fromArray($json);
        $this->assertSame('root', $menu->id);
        $this->assertTrue($menu->hasChildren());
        $this->assertSame(5, $menu->getDepth());

        // Test with limited depth
        $limitedMenu = MenuNode::fromArray($json, 2);
        $this->assertSame('root', $limitedMenu->id);
        $this->assertTrue($limitedMenu->hasChildren());
        $this->assertSame(3, $limitedMenu->getDepth()); // Should stop at level 2 due to maxDepth

        // Verify children IDs are limited by depth
        $limitedIds = $limitedMenu->getAllChildrenIds();
        $this->assertSame(['level1', 'level2'], $limitedIds); // Should not include level3+ due to depth limit
    }

    public function test_menu_node_circular_reference_with_depth_limit(): void
    {
        // Simulate potential circular reference in JSON
        $json = [
            'id' => 'menu-a',
            'label' => 'Menu A',
            'children' => [
                [
                    'id' => 'menu-b',
                    'label' => 'Menu B',
                    'children' => [
                        [
                            'id' => 'menu-c',
                            'label' => 'Menu C',
                            'children' => [
                                [
                                    'id' => 'menu-a', // Circular reference to root
                                    'label' => 'Menu A (circular)',
                                    'children' => [
                                        ['id' => 'menu-b', 'label' => 'Menu B (circular)'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Test with depth limit to prevent infinite recursion
        $menu = MenuNode::fromArray($json, 5);

        $this->assertSame('menu-a', $menu->id);
        $this->assertTrue($menu->hasChildren());

        // Verify the structure is built but limited by depth
        $flattened = $menu->flatten();
        $this->assertGreaterThanOrEqual(3, count($flattened)); // Should have at least 3 nodes

        // Verify we don't get infinite depth
        $this->assertLessThan(10, $menu->getDepth());
    }

    public function test_menu_node_empty_and_edge_cases(): void
    {
        // Test empty children array
        $json = [
            'id' => 'menu-empty',
            'label' => 'Empty Menu',
            'children' => [],
        ];

        $menu = MenuNode::fromArray($json);
        $this->assertSame('menu-empty', $menu->id);
        $this->assertEmpty($menu->children);
        $this->assertFalse($menu->hasChildren());
        $this->assertSame(1, $menu->getDepth());

        // Test malformed child data (invalid children should be skipped)
        $malformedJson = [
            'id' => 'menu-malformed',
            'label' => 'Malformed Menu',
            'children' => [
                'invalid-string-child',
                ['id' => 'valid-child', 'label' => 'Valid Child'],
                null,
                [], // Missing required fields
                ['id' => 'missing-label'], // Missing label
                ['label' => 'missing-id'], // Missing id
            ],
        ];

        $malformedMenu = MenuNode::fromArray($malformedJson);
        $this->assertSame('menu-malformed', $malformedMenu->id);
        $this->assertCount(1, $malformedMenu->children); // Only valid child should be processed
        $this->assertSame('valid-child', $malformedMenu->children[0]->id);

        // Test completely invalid data throws exception
        $this->expectException(InvalidArgumentException::class);
        MenuNode::fromArray(['invalid' => 'data']); // Missing required id and label
    }

    public function test_alternative_id_methods(): void
    {
        $json = [
            'id' => 'test-menu',
            'label' => 'Test Menu',
        ];

        // Test fromArrayWithIds method (should work same as fromArray for MenuNode)
        $menu = MenuNode::fromArrayWithIds($json);
        $this->assertSame('test-menu', $menu->id);
        $this->assertSame('Test Menu', $menu->label);
    }
}
