<?php

namespace Rareloop\Lumberjack\Test\Models;

use Brain\Monkey\Functions;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Models\AbstractPostType;
use Rareloop\Lumberjack\Models\Post;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;
use Timber\PostQuery;
use Timber\Timber;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * The above is required as we're using alias mocks which persist between tests
 * https://laracasts.com/discuss/channels/testing/mocking-a-class-persists-over-tests/replies/103075
 */
class PostTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    // public function testRegisterFunctionCallsRegisterPostTypeWhenPostTypeAndConfigAreProvided()
    // {
    //     Functions\expect('did_action')->once()->andReturn(1);
    //     Functions\expect('get_post_type_object')->once();

    //     RegisterablePostType::register();
    // }

    // public function testRegisterFunctionThrowsExceptionIfConfigIsNotProvided()
    // {
    //     $this->expectException(\Rareloop\Lumberjack\Exceptions\PostTypeRegistrationException::class);
    //     UnregisterablePostTypeWithoutConfig::register();
    // }

    // public function query_defaults_to_current_post_type_and_published()
    // {
    //     $args = [
    //         'posts_per_page' => 10,
    //     ];

    //     $timber = Mockery::mock('alias:' . Timber::class);
    //     $timber->shouldReceive('get_posts')->withArgs([
    //         array_merge($args, [
    //             'post_type' => Post::getPostType(),
    //             'post_status' => 'publish',
    //         ]),
    //         Post::class,
    //     ])->once();

    //     $posts = Post::query($args);

    //     // $this->assertInstanceOf(PostQuery::class, $posts);
    // }

    // /**
    //  * @test
    //  */
    // public function query_ignores_passed_in_post_type()
    // {
    //     $args = [
    //         'posts_per_page' => 10,
    //         'post_type' => 'something-else',
    //     ];

    //     $timber = Mockery::mock('alias:' . Timber::class);
    //     $timber->shouldReceive('get_posts')->withArgs([
    //         array_merge($args, [
    //             'post_type' => Post::getPostType(),
    //             'post_status' => 'publish',
    //         ]),
    //         Post::class,
    //     ])->once();

    //     $posts = Post::query($args);

    //     $this->assertInstanceOf(PostQuery::class, $posts);
    // }

    // /**
    //  * @test
    //  */
    // public function post_subclass_query_has_correct_post_type()
    // {
    //     $args = [
    //         'posts_per_page' => 10,
    //     ];

    //     $timber = Mockery::mock('alias:' . Timber::class);
    //     $timber->shouldReceive('get_posts')->withArgs([
    //         Mockery::subset([
    //             'post_type' => RegisterablePostType::getPostType(),
    //         ]),
    //         RegisterablePostType::class,
    //     ])->once();

    //     $posts = RegisterablePostType::query($args);

    //     $this->assertInstanceOf(PostQuery::class, $posts);
    // }

    // /**
    //  * @test
    //  */
    // public function query_can_have_post_status_overwritten()
    // {
    //     $args = [
    //         'post_status' => ['draft', 'publish'],
    //     ];

    //     $timber = Mockery::mock('alias:' . Timber::class);
    //     $timber->shouldReceive('get_posts')->withArgs([
    //         Mockery::subset([
    //             'post_status' => ['draft', 'publish'],
    //         ]),
    //         Post::class,
    //     ])->once();

    //     $posts = Post::query($args);

    //     $this->assertInstanceOf(PostQuery::class, $posts);
    // }

    // /**
    //  * @test
    //  */
    // public function all_defaults_to_unlimited_ordered_by_menu_order_ascending()
    // {
    //     $timber = Mockery::mock('alias:' . Timber::class);
    //     $timber->shouldReceive('get_posts')->withArgs([
    //         Mockery::subset([
    //             'posts_per_page' => -1,
    //             'orderby' => 'menu_order',
    //             'order' => 'ASC',
    //         ]),
    //         Post::class,
    //     ])->once();

    //     $posts = Post::all();

    //     $this->assertInstanceOf(Collection::class, $posts);
    // }

    // /**
    //  * @test
    //  */
    // public function all_can_have_post_limit_set()
    // {
    //     $timber = Mockery::mock('alias:' . Timber::class);
    //     $timber->shouldReceive('get_posts')->withArgs([
    //         Mockery::subset([
    //             'posts_per_page' => 10,
    //         ]),
    //         Post::class,
    //     ])->once();

    //     $posts = Post::all(10);

    //     $this->assertInstanceOf(PostQuery::class, $posts);
    // }

    // /**
    //  * @test
    //  */
    // public function all_can_have_order_set()
    // {
    //     $timber = Mockery::mock('alias:' . Timber::class);
    //     $timber->shouldReceive('get_posts')->withArgs([
    //         Mockery::subset([
    //             'orderby' => 'date',
    //             'order' => 'DESC',
    //         ]),
    //         Post::class,
    //     ])->once();

    //     $posts = Post::all(-1, 'date', 'DESC');

    //     $this->assertInstanceOf(PostQuery::class, $posts);
    // }

    public function testCanExtendPostBehaviourWithMacros()
    {
        Post::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $post = new Post(false, true);

        $this->assertSame('abc123', $post->testFunctionAddedByMacro());
        $this->assertSame('abc123', Post::testFunctionAddedByMacro());
    }

    public function testMacrosSetCorrectThisContextOnInstances()
    {
        Post::macro('testFunctionAddedByMacro', function () {
            return $this->dummyData();
        });

        $post = new Post(false, true);
        $post->dummyData = 'abc123';

        $this->assertSame('abc123', $post->testFunctionAddedByMacro());
    }

    public function testCanExtendPostBehaviourWithMixin()
    {
        Post::mixin(new PostMixin());

        $post = new Post(false, true);

        $this->assertSame('abc123', $post->testFunctionAddedByMixin());
    }
}

class PostMixin
{
    public function testFunctionAddedByMixin()
    {
        return function () {
            return 'abc123';
        };
    }
}

class RegisterablePostType extends AbstractPostType
{
    public static function getPostType(): string
    {
        return 'registerable_post_type';
    }

    public static function getPrivateConfig()
    {
        return self::getPostTypeConfig();
    }

    protected static function getPostTypeConfig(): array
    {
        return [
            'labels' => [
                'name'          => 'Groups',
                'singular_name' => 'Group',
            ],
            'public'      => true,
            'has_archive' => false,
            'supports'    => ['title', 'revisions'],
            'menu_icon'   => 'dashicons-groups',
            'rewrite'     => [
                'slug' => 'group',
            ],
        ];
    }
}

class UnregisterablePostTypeWithoutConfig extends AbstractPostType
{
    public static function getPostType(): string
    {
        return 'post_type';
    }
}
