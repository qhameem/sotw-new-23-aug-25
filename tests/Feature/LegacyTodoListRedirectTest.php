<?php

namespace Tests\Feature;

use App\Support\ToolSettings;
use Tests\TestCase;

class LegacyTodoListRedirectTest extends TestCase
{
    public function test_legacy_base_url_redirects_to_the_configured_tool(): void
    {
        $this->mock(ToolSettings::class)
            ->shouldReceive('path')
            ->once()
            ->with(ToolSettings::TODO_LIST_KEY)
            ->andReturn('/tools/custom-todo');

        $this->get('/free-todo-list-tool')
            ->assertStatus(301)
            ->assertRedirect('/tools/custom-todo');
    }

    public function test_legacy_nested_url_preserves_its_path_and_query_string(): void
    {
        $this->mock(ToolSettings::class)
            ->shouldReceive('path')
            ->once()
            ->with(ToolSettings::TODO_LIST_KEY)
            ->andReturn('/tools/custom-todo');

        $this->get('/free-todo-list-tool/lists/42?view=compact&sort=due')
            ->assertStatus(301)
            ->assertRedirect('/tools/custom-todo/lists/42?sort=due&view=compact');
    }
}
