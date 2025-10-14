<div x-data="taskList()" x-init="init()" class="space-y-6">
    <form @submit.prevent="create" class="flex items-start gap-3">
        <div class="flex-1">
            <label class="sr-only" for="title">Başlık</label>
            <input id="title" x-model="form.title" type="text" placeholder="Yeni görev..."
                   class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <input x-model="form.due_at" type="datetime-local" class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <select x-model.number="form.priority" class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="0">Öncelik: Normal</option>
            <option value="1">Öncelik: Düşük</option>
            <option value="2">Öncelik: Orta</option>
            <option value="3">Öncelik: Yüksek</option>
            <option value="4">Öncelik: Acil</option>
            <option value="5">Öncelik: Kritik</option>
        </select>
        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Ekle</button>
    </form>

    <template x-if="loading">
        <div class="text-gray-500">Yükleniyor...</div>
    </template>

    <ul class="divide-y divide-gray-200 bg-white rounded-md shadow">
        <template x-for="task in tasks" :key="task.id">
            <li class="flex items-center gap-3 p-3">
                <input type="checkbox" :checked="task.is_completed" @change="toggle(task)"
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium" :class="task.is_completed ? 'line-through text-gray-400' : ''" x-text="task.title"></span>
                        <span x-show="task.priority > 0" class="text-xs rounded-full bg-gray-100 px-2 py-0.5" x-text="'P'+task.priority"></span>
                    </div>
                    <div class="text-xs text-gray-500" x-show="task.due_at" x-text="formatDate(task.due_at)"></div>
                </div>
                <button @click="remove(task)" class="text-sm text-red-600 hover:underline">Sil</button>
            </li>
        </template>
        <template x-if="!loading && tasks.length === 0">
            <li class="p-4 text-center text-gray-500">Henüz görev yok</li>
        </template>
    </ul>

    <script>
        function taskList() {
            return {
                tasks: [],
                loading: false,
                form: { title: '', description: null, due_at: null, priority: 0, sort_order: null },
                async init() {
                    await this.fetchTasks();
                },
                async fetchTasks() {
                    this.loading = true;
                    try {
                        const res = await fetch('/tasks', { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Görevler yüklenemedi');
                        this.tasks = await res.json();
                    } finally {
                        this.loading = false;
                    }
                },
                async create() {
                    const res = await fetch('/tasks', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? ''
                        },
                        body: JSON.stringify(this.form)
                    });
                    if (res.ok) {
                        const task = await res.json();
                        this.tasks.unshift(task);
                        this.form.title = '';
                        this.form.due_at = null;
                        this.form.priority = 0;
                    }
                },
                async toggle(task) {
                    const res = await fetch(`/tasks/${task.id}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? ''
                        }
                    });
                    if (res.ok) {
                        const updated = await res.json();
                        const idx = this.tasks.findIndex(t => t.id === task.id);
                        if (idx !== -1) this.tasks[idx] = updated;
                    }
                },
                async remove(task) {
                    const res = await fetch(`/tasks/${task.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? ''
                        }
                    });
                    if (res.ok) {
                        this.tasks = this.tasks.filter(t => t.id !== task.id);
                    }
                },
                formatDate(d) {
                    const date = new Date(d);
                    return date.toLocaleString();
                }
            }
        }
    </script>
</div>
