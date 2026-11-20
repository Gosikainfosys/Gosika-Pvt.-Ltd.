<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskPrioritySortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_task_board_sort_tasks_by_priority(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        foreach (['medium', 'urgent', 'high'] as $priority) {
            Task::create([
                'title' => ucfirst($priority) . ' task',
                'due_date' => '2026-12-01',
                'priority' => $priority,
                'status' => 'todo',
            ]);
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('urgentTasks.0.priority', 'urgent')
                ->where('urgentTasks.1.priority', 'high')
                ->where('urgentTasks.2.priority', 'medium')
            );

        $this->get(route('tasks.kanban'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tasks.0.priority', 'urgent')
                ->where('tasks.1.priority', 'high')
                ->where('tasks.2.priority', 'medium')
            );
    }
}
