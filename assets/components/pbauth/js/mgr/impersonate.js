/**
 * «Авторизоваться на сайте» для менеджера MODX 3 (ExtJS).
 *
 * Добавляет пункт в контекстное меню грида пользователей (MODx.grid.User) и
 * кнопку на странице пользователя. И то и другое открывает /impersonate/{id} в
 * новой вкладке; права там проверяет ImpersonateController — нужна сессия
 * менеджера с флагом sudo.
 *
 * Кнопка на странице цепляется к кнопке «Сохранить» (у неё стабильный id
 * 'modx-abtn-save', заданный в MODx.page.UpdateUser) и попадает в ту же панель.
 */
Ext.onReady(function () {
    if (typeof MODx === 'undefined' || typeof Ext === 'undefined') {
        return;
    }

    var LABEL = (window.pbAuth && pbAuth.lang && pbAuth.lang.impersonate)
        || 'Log in as user';

    var openImpersonate = function (id) {
        if (id) {
            window.open('/impersonate/' + id, '_blank');
        }
    };

    // --- Грид пользователей: пункт в контекстном меню ------------------
    if (MODx.grid && MODx.grid.User) {
        var origGetMenu = MODx.grid.User.prototype.getMenu;

        Ext.override(MODx.grid.User, {
            getMenu: function () {
                var menu = origGetMenu ? origGetMenu.apply(this, arguments) : (this.menu || []);
                if (!Ext.isArray(menu)) {
                    menu = this.menu || [];
                }

                var grid = this;
                menu.push('-', {
                    text: LABEL,
                    handler: function () {
                        var rec = (grid.menu && grid.menu.record)
                            || (grid.getSelectionModel && grid.getSelectionModel().getSelected());
                        var id = rec && (rec.id || (rec.data && rec.data.id));
                        openImpersonate(id);
                    }
                });

                return menu;
            }
        });
    }

    // --- Страница пользователя: кнопка рядом с «Сохранить» -------------
    // Кнопки действий живут на странице (MODx.page.UpdateUser), а не на панели.
    // Только на странице редактирования — там есть ?id=.
    var userId = MODx.request ? MODx.request.id : null;
    if (!userId) {
        return;
    }

    var addPageButton = function () {
        var saveBtn = Ext.getCmp('modx-abtn-save');
        if (!saveBtn || !saveBtn.ownerCt) {
            return false;
        }

        var toolbar = saveBtn.ownerCt;
        if (toolbar._pbAuthImpersonateBtn) {
            return true;
        }
        toolbar._pbAuthImpersonateBtn = true;

        toolbar.add({
            xtype: 'button',
            text: LABEL,
            cls: 'primary-button',
            style: 'margin-left:6px;',
            handler: function () { openImpersonate(userId); }
        });
        toolbar.doLayout();
        return true;
    };

    // Страница дорисовывается уже после onReady — недолго пробуем повторно.
    var tries = 0;
    var timer = setInterval(function () {
        if (addPageButton() || ++tries > 20) {
            clearInterval(timer);
        }
    }, 300);
});
