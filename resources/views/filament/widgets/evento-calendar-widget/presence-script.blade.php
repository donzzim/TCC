    @once
        @script
            <script>
                if (!window.cronogramaPresence) {
                    window.cronogramaPresence = function (config) {
                        return {
                            keys: Array.isArray(config.keys) ? config.keys.filter(Boolean) : [],
                            currentUser: config.currentUser || null,
                            users: {},
                            timer: null,
                            refreshTimer: null,
                            leaving: false,

                            init() {
                                this.refresh();
                                this.timer = window.setInterval(() => this.refresh(), 12000);
                                window.addEventListener('beforeunload', () => this.leaveAll());
                            },

                            destroy() {
                                this.leaveAll();
                                window.clearInterval(this.timer);
                                window.clearTimeout(this.refreshTimer);
                            },

                            usersFor(key) {
                                if (!key || !Array.isArray(this.users[key])) {
                                    return [];
                                }

                                return this.users[key];
                            },

                            allUsers() {
                                const byId = new Map();

                                Object.values(this.users || {}).forEach((users) => {
                                    if (!Array.isArray(users)) {
                                        return;
                                    }

                                    users.forEach((user) => {
                                        if (!user || !user.id) {
                                            return;
                                        }

                                        const id = Number(user.id);
                                        const existing = byId.get(id);

                                        if (!existing) {
                                            byId.set(id, { ...user });
                                            return;
                                        }

                                        existing.area_labels = Array.from(new Set([
                                            ...(Array.isArray(existing.area_labels) ? existing.area_labels : []),
                                            ...(Array.isArray(user.area_labels) ? user.area_labels : []),
                                        ]));
                                    });
                                });

                                return Array.from(byId.values())
                                    .sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
                            },

                            extraCount(key) {
                                return Math.max(this.usersFor(key).length - 3, 0);
                            },

                            userTooltip(user) {
                                const labels = Array.isArray(user.area_labels) && user.area_labels.length
                                    ? ` - ${user.area_labels.join(', ')}`
                                    : '';

                                return `${user.name || 'Usuario'}${labels}`;
                            },

                            extraTooltip(key) {
                                return this.usersFor(key)
                                    .slice(3)
                                    .map((user) => this.userTooltip(user))
                                    .join('\n');
                            },

                            avatarStyle(user) {
                                return `background-color: ${user.color || '#2563eb'}`;
                            },

                            localEnter(detail) {
                                if (!detail || !detail.key || !this.currentUser) {
                                    return;
                                }

                                const key = detail.key;
                                const area = detail.area || 'formulario';
                                const currentUsers = this.usersFor(key).filter((user) => Number(user.id) !== Number(this.currentUser.id));
                                const currentUser = {
                                    ...this.currentUser,
                                    areas: [area],
                                    area_labels: [this.areaLabel(area)],
                                };

                                this.users = {
                                    ...this.users,
                                    [key]: [currentUser, ...currentUsers],
                                };

                                this.refreshSoon(6000);
                            },

                            areaLabel(area) {
                                return {
                                    agenda: 'reagendando',
                                    titulo: 'editando titulo',
                                    instrutores: 'editando docentes',
                                    local: 'editando local',
                                    formulario: 'editando formulario',
                                }[area] || 'editando formulario';
                            },

                            refreshSoon(delay = 250) {
                                window.clearTimeout(this.refreshTimer);
                                this.refreshTimer = window.setTimeout(() => this.refresh(), delay);
                            },

                            async refresh() {
                                if (!this.keys.length) {
                                    this.users = {};
                                    return;
                                }

                                try {
                                    const response = await this.$wire.buscarPresencasCronograma(this.keys);
                                    this.users = response || {};
                                } catch (error) {
                                    // Presence is a helper UI. Fail silently to avoid blocking the schedule.
                                }
                            },

                            async leaveAll() {
                                if (this.leaving || !this.currentUser) {
                                    return;
                                }

                                this.leaving = true;

                                const calls = [];

                                Object.entries(this.users || {}).forEach(([key, users]) => {
                                    const user = Array.isArray(users)
                                        ? users.find((item) => Number(item.id) === Number(this.currentUser.id))
                                        : null;

                                    if (!user) {
                                        return;
                                    }

                                    const areas = Array.isArray(user.areas) && user.areas.length
                                        ? user.areas
                                        : ['formulario'];

                                    areas.forEach((area) => {
                                        calls.push(this.$wire.sairPresencaCronograma(key, area).catch(() => null));
                                    });
                                });

                                await Promise.all(calls);
                                this.leaving = false;
                            },
                        };
                    };
                }
            </script>
        @endscript
    @endonce
