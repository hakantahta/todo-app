<div x-data="taskList()" x-init="init()" class="space-y-6">
    <!-- Toast container -->
    <div class="fixed inset-x-0 top-4 z-50 flex justify-center" aria-live="assertive">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="mx-2 rounded-md px-4 py-2 shadow text-white"
                 :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
                 x-text="toast.message"></div>
        </template>
    </div>
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

    <!-- Arama ve filtreler -->
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[220px]">
            <input x-model="filters.q" type="text" placeholder="Görevlerde ara..."
                   class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <select x-model="filters.status" class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="all">Tümü</option>
            <option value="active">Aktif</option>
            <option value="completed">Tamamlanmış</option>
        </select>
        <select x-model.number="filters.priority" class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option :value="null">Öncelik (hepsi)</option>
            <template x-for="n in [0,1,2,3,4,5]" :key="n">
                <option :value="n">P<n x-text="n"></n></option>
            </template>
        </select>
        <button type="button" @click="clearFilters" class="text-sm text-gray-600 hover:underline">Temizle</button>
    </div>

    <template x-if="loading">
        <div class="text-gray-500">Yükleniyor...</div>
    </template>

    <ul class="divide-y divide-gray-200 bg-white rounded-md shadow">
        <template x-for="task in sortedTasks" :key="task.id">
            <li class="flex items-center gap-3 p-3 rounded" :class="task.is_completed ? 'bg-green-50' : 'bg-sky-50'">
                <input type="checkbox" :checked="task.is_completed" @change="toggle(task)"
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span :class="'inline-block h-2.5 w-2.5 rounded-full ' + priorityClass(task.priority)" aria-hidden="true"></span>
                        <template x-if="editId !== task.id">
                            <span class="font-medium cursor-pointer" @click="beginEdit(task)" :class="task.is_completed ? 'line-through text-gray-400' : ''" x-text="task.title" ></span>
                        </template>
                        <template x-if="editId === task.id">
                            <form @submit.prevent="saveEdit(task)" class="flex items-center gap-2">
                                <input x-model="editTitle" class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
                                <button type="submit" class="text-sm text-indigo-600 hover:underline">Kaydet</button>
                                <button type="button" @click="cancelEdit()" class="text-sm text-gray-500 hover:underline">Vazgeç</button>
                            </form>
                        </template>
                        <span x-show="task.priority > 0" class="text-xs rounded-full bg-gray-100 px-2 py-0.5" x-text="'P'+task.priority"></span>
                    </div>
                    <div class="text-xs text-gray-500" x-show="task.due_at">
                        Son tarih: <span x-text="formatDate(task.due_at)"></span>
                    </div>
                    <div class="text-xs text-gray-500">
                        Oluşturuldu: <span x-text="formatDate(task.created_at)"></span>
                    </div>
                    <p x-show="errors[task.id]" class="text-sm text-red-600" x-text="errors[task.id]"></p>
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
                editId: null,
                editTitle: '',
                errors: {},
                toasts: [],
                filters: { q: '', status: 'all', priority: null },
                async init() {
                    await this.fetchTasks();
                    // Filtreler değiştikçe backend'den veri çek
                    this.$watch('filters.q', () => this.fetchTasks());
                    this.$watch('filters.status', () => this.fetchTasks());
                    this.$watch('filters.priority', () => this.fetchTasks());
                },
                get sortedTasks() {
                    // Sırala: aktifler önce, öncelik, due_at, sort_order (filtreler backend'de)
                    return [...this.tasks].sort((a,b) => {
                        if (a.is_completed !== b.is_completed) return a.is_completed - b.is_completed; // false önce
                        if (a.priority !== b.priority) return a.priority - b.priority;
                        if (a.due_at && b.due_at) return new Date(a.due_at) - new Date(b.due_at);
                        if (a.due_at && !b.due_at) return -1;
                        if (!a.due_at && b.due_at) return 1;
                        return (a.sort_order ?? 0) - (b.sort_order ?? 0);
                    });
                },
                async fetchTasks() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.q) params.set('q', this.filters.q);
                        if (this.filters.status && this.filters.status !== 'all') params.set('status', this.filters.status);
                        if (this.filters.priority !== null) params.set('priority', this.filters.priority);
                        const res = await fetch(`/tasks?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Görevler yüklenemedi');
                        this.tasks = await res.json();
                        this.toast('Görevler yüklendi', 'success');
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
                        this.errors = {};
                        this.toast('Görev eklendi', 'success');
                    } else if (res.status === 422) {
                        const data = await res.json();
                        this.errors = { create: Object.values(data.errors).flat().join(' ') };
                        this.toast('Doğrulama hatası: ' + this.errors.create, 'error');
                    } else {
                        this.toast('Görev ekleme başarısız', 'error');
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
                        this.toast(updated.is_completed ? 'Görev tamamlandı' : 'Görev aktif edildi', 'success');
                    } else {
                        this.toast('Durum değiştirilemedi', 'error');
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
                        this.toast('Görev silindi', 'success');
                    } else {
                        this.toast('Görev silinemedi', 'error');
                    }
                },
                beginEdit(task) {
                    this.editId = task.id;
                    this.editTitle = task.title;
                    delete this.errors[task.id];
                },
                cancelEdit() { this.editId = null; this.editTitle = ''; },
                async saveEdit(task) {
                    const res = await fetch(`/tasks/${task.id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? ''
                        },
                        body: JSON.stringify({ title: this.editTitle })
                    });
                    if (res.ok) {
                        const updated = await res.json();
                        const idx = this.tasks.findIndex(t => t.id === task.id);
                        if (idx !== -1) this.tasks[idx] = updated;
                        this.cancelEdit();
                        this.toast('Görev güncellendi', 'success');
                    } else if (res.status === 422) {
                        const data = await res.json();
                        this.errors = { ...this.errors, [task.id]: Object.values(data.errors).flat().join(' ') };
                        this.toast('Doğrulama hatası: ' + this.errors[task.id], 'error');
                    } else {
                        this.toast('Görev güncellenemedi', 'error');
                    }
                },
                formatDate(d) {
                    const date = new Date(d);
                    return date.toLocaleString();
                },
                priorityClass(n) {
                    switch (Number(n)) {
                        case 5: return 'bg-red-600';
                        case 4: return 'bg-orange-500';
                        case 3: return 'bg-amber-400';
                        case 2: return 'bg-yellow-400';
                        case 1: return 'bg-lime-400';
                        default: return 'bg-green-500';
                    }
                },
                toast(message, type = 'success') {
                    const id = Math.random().toString(36).slice(2);
                    this.toasts.push({ id, message, type });
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id) }, 2500);
                },
                clearFilters() { this.filters = { q: '', status: 'all', priority: null }; }
            }
        }
    </script>
</div>
