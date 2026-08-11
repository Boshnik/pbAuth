/**
 * Кнопки на пользователя в менеджере MODX 3 (ExtJS).
 *
 * Две штуки, каждая включается отдельно:
 *   «Авторизоваться на сайте» — открывает сайт под этим пользователем
 *                               (см. ImpersonateController, нужна сессия sudo);
 *   «Посмотреть на сайте»     — открывает публичную страницу пользователя по
 *                               шаблону из системной настройки pbauth_user_page.
 *
 * Обе появляются в контекстном меню грида пользователей (MODx.grid.User) и на
 * странице пользователя рядом с «Сохранить». Кнопка на странице цепляется к
 * кнопке сохранения (у неё стабильный id 'modx-abtn-save') и попадает в ту же
 * панель.
 */
Ext.onReady(function () {
    if (typeof MODx === 'undefined' || typeof Ext === 'undefined') {
        return;
    }

    var cfg = (window.pbAuth && pbAuth.mgr) || {};
    var lang = cfg.lang || {};

    // Иконки шрифта менеджера, как их подключает PageBlocks: разметкой прямо в
    // подписи, а не через iconCls — в контекстном меню грида iconCls не виден.
    var withIcon = function (icon, text) {
        return '<i class="icon icon-' + icon + '"></i> ' + text;
    };
    var impersonate = !!cfg.impersonate;
    var template = cfg.userPage || '';

    if (!impersonate && !template) {
        return;
    }

    var openImpersonate = function (id) {
        if (id) {
            window.open('/impersonate/' + id, '_blank');
        }
    };

    /**
     * Собирает адрес страницы пользователя из шаблона вида `users/{id}`.
     * Абсолютный адрес в настройке оставляем как есть — сайт может быть и на
     * другом домене.
     */
    var userPageUrl = function (id, username) {
        if (!id || !template) {
            return '';
        }

        var url = template
            .replace(/\{id\}/g, id)
            .replace(/\{username\}/g, encodeURIComponent(username || ''));

        if (/^https?:\/\//i.test(url)) {
            return url;
        }

        // `\/*$` вместо `\/+$`: адрес сайта без слэша на конце иначе склеился бы
        // с путём в «example.comusers/1».
        var base = (MODx.config && MODx.config.site_url) || '/';

        return base.replace(/\/*$/, '/') + url.replace(/^\/+/, '');
    };

    var openUserPage = function (id, username) {
        var url = userPageUrl(id, username);
        if (url) {
            window.open(url, '_blank');
        }
    };

    // --- Грид пользователей: пункты в контекстном меню ------------------
    if (MODx.grid && MODx.grid.User) {
        var origGetMenu = MODx.grid.User.prototype.getMenu;

        Ext.override(MODx.grid.User, {
            getMenu: function () {
                var menu = origGetMenu ? origGetMenu.apply(this, arguments) : (this.menu || []);
                if (!Ext.isArray(menu)) {
                    menu = this.menu || [];
                }

                var grid = this;
                var record = function () {
                    return (grid.menu && grid.menu.record)
                        || (grid.getSelectionModel && grid.getSelectionModel().getSelected());
                };
                var field = function (rec, name) {
                    if (!rec) {
                        return null;
                    }
                    return rec[name] !== undefined ? rec[name] : (rec.data && rec.data[name]);
                };

                menu.push('-');

                if (template) {
                    menu.push({
                        text: withIcon('eye', lang.view || 'View on the site'),
                        handler: function () {
                            var rec = record();
                            openUserPage(field(rec, 'id'), field(rec, 'username'));
                        }
                    });
                }

                if (impersonate) {
                    menu.push({
                        text: withIcon('sign-in', lang.impersonate || 'Log in as user'),
                        handler: function () {
                            openImpersonate(field(record(), 'id'));
                        }
                    });
                }

                return menu;
            }
        });
    }

    // --- Страница пользователя: кнопки рядом с «Сохранить» -------------
    // Кнопки действий живут на странице (MODx.page.UpdateUser), а не на панели.
    // Только на странице редактирования — там есть ?id=.
    var userId = MODx.request ? MODx.request.id : null;
    if (!userId) {
        return;
    }

    var addPageButtons = function () {
        var saveBtn = Ext.getCmp('modx-abtn-save');
        if (!saveBtn || !saveBtn.ownerCt) {
            return false;
        }

        var toolbar = saveBtn.ownerCt;
        if (toolbar._pbAuthButtons) {
            return true;
        }
        toolbar._pbAuthButtons = true;

        if (template) {
            toolbar.add({
                xtype: 'button',
                text: withIcon('eye', lang.view || 'View on the site'),
                handler: function () { openUserPage(userId, cfg.username); }
            });
        }

        if (impersonate) {
            toolbar.add({
                xtype: 'button',
                text: withIcon('sign-in', lang.impersonate || 'Log in as user'),
                cls: 'primary-button',
                handler: function () { openImpersonate(userId); }
            });
        }

        toolbar.doLayout();
        return true;
    };

    // Страница дорисовывается уже после onReady — недолго пробуем повторно.
    var tries = 0;
    var timer = setInterval(function () {
        if (addPageButtons() || ++tries > 20) {
            clearInterval(timer);
        }
    }, 300);
});
