<?php

namespace Thetheago\SeederVersioning\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thetheago\SeederVersioning\Services\SeederRiskAnalyzer;

class SeederRiskAnalyzerTest extends TestCase
{
    public static function risks(): array
    {
        return [
            'truncate on query builder' => [
                "DB::table('users')->truncate();",
                [['high', 'truncate() on table users']],
            ],
            'delete on model' => [
                "User::where('active', false)->delete();",
                [['high', 'delete() on User']],
            ],
            'truncate on fully qualified model' => [
                '\App\Models\Product::truncate();',
                [['high', 'truncate() on Product']],
            ],
            'raw DROP statement' => [
                "DB::statement('DROP TABLE logs');",
                [['high', 'statement() with DROP']],
            ],
            'harmless raw statement' => [
                "DB::statement('SET NAMES utf8');",
                [],
            ],
            'insert without upsert' => [
                "DB::table('users')->insert(['name' => 'a']);",
                [['medium', 'insert() without upsert']],
            ],
            'insert next to updateOrCreate' => [
                "DB::table('logs')->insert([]); User::updateOrCreate(['email' => 'a'], []);",
                [],
            ],
            'upsert only' => [
                "DB::table('users')->upsert([], ['email']);",
                [],
            ],
            'calls other seeders' => [
                '$this->call([UserSeeder::class]);',
                [['medium', '$this->call() runs other seeders']],
            ],
            'factory' => [
                'User::factory()->count(10)->create();',
                [['medium', 'factory() creates random data'], ['medium', 'create() without upsert']],
            ],
            'factory helper' => [
                'factory(User::class, 3)->make();',
                [['medium', 'factory() creates random data']],
            ],
            'disables foreign keys' => [
                'Schema::disableForeignKeyConstraints();',
                [['medium', 'disableForeignKeyConstraints() disables foreign keys']],
            ],
            'high risks come first' => [
                "Schema::disableForeignKeyConstraints(); DB::table('roles')->truncate();",
                [['high', 'truncate() on table roles'], ['medium', 'disableForeignKeyConstraints() disables foreign keys']],
            ],
            'comments and strings are ignored' => [
                "// DB::table('users')->truncate();\n/* User::truncate(); */\n\$text = 'truncate()';",
                [],
            ],
        ];
    }

    #[DataProvider('risks')]
    public function test_detectsRisks(string $code, array $expected): void
    {
        $risks = (new SeederRiskAnalyzer())->analyze("<?php\n{$code}");

        $this->assertSame(
            array_map(fn (array $risk) => ['level' => $risk[0], 'message' => $risk[1]], $expected),
            $risks
        );
    }
}
