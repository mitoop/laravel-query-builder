<?php

namespace Mitoop\LaravelQueryBuilder\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Support\LikeClause;
use Mitoop\LaravelQueryBuilder\Support\Operators\LikeOperator;
use Mitoop\LaravelQueryBuilder\Tests\Fixtures\LikeUser;
use Mitoop\LaravelQueryBuilder\ValueResolvers\Like;
use Mitoop\LaravelQueryBuilder\ValueResolvers\LikeAny;

class LikeClauseTest extends PackageTestCase
{
    public function test_pattern_escapes_like_wildcards(): void
    {
        $this->assertSame('%100\%\_a%', LikeClause::pattern('100%_a'));
        $this->assertSame('%a\\\\b%', LikeClause::pattern('a\\b'));
    }

    public function test_pattern_removes_control_characters_and_keeps_newlines(): void
    {
        $this->assertSame("%a\nb%", LikeClause::pattern("a\nb"));
        $this->assertSame('%ab%', LikeClause::pattern("a\tb"));
        $this->assertSame('%ab%', LikeClause::pattern("a\u{200B}b"));
    }

    public function test_pattern_is_empty_when_nothing_searchable_remains(): void
    {
        $this->assertSame('', LikeClause::pattern("\t\r"));
        $this->assertSame('', LikeClause::pattern("a\xC3\x28b"));
    }

    public function test_like_resolver_honors_a_custom_prefix_and_suffix(): void
    {
        $this->assertSame('100\%\_a%', (new Like('', '%'))->resolve('100%_a'));
        $this->assertSame('%100\%\_a', (new Like('%', ''))->resolve('100%_a'));
    }

    public function test_like_any_resolver_reuses_the_same_pattern(): void
    {
        [$fields, $pattern] = (new LikeAny(['name', 'email']))->resolve('100%_a');

        $this->assertSame(['name', 'email'], $fields);
        $this->assertSame(LikeClause::pattern('100%_a'), $pattern);
    }

    public function test_where_and_or_where_select_the_boolean(): void
    {
        $builder = LikeUser::query();
        LikeClause::where($builder, 'email', '%a%');
        LikeClause::orWhere($builder, 'name', '%b%');

        $wheres = $builder->getQuery()->wheres;

        $this->assertSame('and', $wheres[0]['boolean']);
        $this->assertSame('or', $wheres[1]['boolean']);
        $this->assertSame(['%a%', '\\', '%b%', '\\'], $builder->getBindings());
        $this->assertSame(2, substr_count($builder->toSql(), 'like ? escape ?'));
    }

    public function test_like_operator_adds_an_escape_clause(): void
    {
        $builder = LikeUser::query()->filter([
            'email|like' => new Like,
        ], [
            'email' => '100%_a',
        ]);

        $this->assertStringContainsString('"email" like ? escape ?', $builder->toSql());
        $this->assertSame(['%100\%\_a%', '\\'], $builder->getBindings());
    }

    public function test_like_operator_can_combine_with_or(): void
    {
        $builder = LikeUser::query();

        (new LikeOperator)->apply($builder, 'orWhere', 'email', '%100\%\_a%');

        $this->assertSame('or', $builder->getQuery()->wheres[0]['boolean']);
        $this->assertSame(['%100\%\_a%', '\\'], $builder->getBindings());
    }

    public function test_a_keyword_that_cleans_to_empty_adds_no_constraint(): void
    {
        $builder = LikeUser::query()->filter([
            'email|like' => new Like,
        ], [
            'email' => "\t",
        ]);

        $this->assertSame([], $builder->getBindings());
        $this->assertStringNotContainsString('like', $builder->toSql());
    }

    public function test_like_operator_rejects_an_empty_pattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword cannot be empty.');

        (new LikeOperator)->apply(LikeUser::query(), 'where', 'email', '');
    }

    public function test_like_any_escapes_columns_and_relation_fields(): void
    {
        $builder = LikeUser::query()->filter([
            'keyword|like_any' => new LikeAny([
                'name',
                'profile' => ['bio'],
            ]),
        ], [
            'keyword' => '100%_a',
        ]);

        $sql = $builder->toSql();

        $this->assertStringContainsString('"name" like ? escape ?', $sql);
        $this->assertStringContainsString('"bio" like ? escape ?', $sql);
        $this->assertStringContainsString('exists', $sql);
        $this->assertSame(['%100\%\_a%', '\\', '%100\%\_a%', '\\'], $builder->getBindings());
    }

    public function test_sqlite_matches_literal_percent_underscore_and_backslash(): void
    {
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
        });

        LikeUser::query()->insert([
            ['name' => 'percent', 'email' => '100%_a@example.com'],
            ['name' => 'wide', 'email' => '100XXa@example.com'],
            ['name' => 'under', 'email' => '100_a@example.com'],
            ['name' => 'single', 'email' => '100Xa@example.com'],
            ['name' => 'slash', 'email' => 'a\\b@example.com'],
            ['name' => 'project-hit', 'email' => 'other@example.com'],
        ]);

        $this->assertSame(['percent'], $this->namesForEmail('100%_a'));
        $this->assertSame(['under'], $this->namesForEmail('100_a'));
        $this->assertSame(['slash'], $this->namesForEmail('a\\b'));

        $pattern = LikeClause::pattern('hit');
        $names = LikeUser::query()->where(function ($query) use ($pattern): void {
            LikeClause::where($query, 'name', $pattern);
            LikeClause::orWhere($query, 'email', $pattern);
        })->orderBy('id')->pluck('name')->all();

        $this->assertSame(['project-hit'], $names);
    }

    /**
     * @return list<string>
     */
    private function namesForEmail(string $keyword): array
    {
        return LikeUser::query()->filter([
            'email|like' => new Like,
        ], [
            'email' => $keyword,
        ])->orderBy('id')->pluck('name')->all();
    }
}
