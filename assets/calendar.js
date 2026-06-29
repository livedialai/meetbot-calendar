/**
 * MeetBot Calendar – https://gofonia.de
 * Copyright (C) 2026 GoFonIA
 * Licensed under GNU GPL v2 or later (GPL-2.0-or-later)
 * https://www.gnu.org/licenses/gpl-2.0.html
 */
(function() {
    'use strict';

    var SLOTS_VISIBLE = 6;

    document.addEventListener('DOMContentLoaded', function() {
        var app = document.getElementById('meetbot-app');
        if (!app) return;

        var pageUrl     = app.dataset.page;
        var duration    = parseInt(app.dataset.duration) || 30;
        var googleMeet  = app.dataset.googleMeet === '1';
        var i18n        = (window.meetbotCal && meetbotCal.i18n) || {};

        var state = {
            step: 1,
            calYear: null,
            calMonth: null,
            selectedDate: null,
            selectedSlot: null
        };

        var months = ['Januar','Februar','M\u00e4rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
        var dayHeaders = ['Mo','Di','Mi','Do','Fr','Sa','So'];
        var dayNames = ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];

        initCalendar();
        loadAllSlots();

        function initCalendar() {
            var now = new Date();
            state.calYear = now.getFullYear();
            state.calMonth = now.getMonth();

            document.getElementById('meetbot-cal-prev').addEventListener('click', function() {
                state.calMonth--;
                if (state.calMonth < 0) { state.calMonth = 11; state.calYear--; }
                renderCalendar();
            });
            document.getElementById('meetbot-cal-next').addEventListener('click', function() {
                state.calMonth++;
                if (state.calMonth > 11) { state.calMonth = 0; state.calYear++; }
                renderCalendar();
            });
        }

        function loadAllSlots() {
            var fd = new FormData();
            fd.append('action', 'meetbot_get_slots');
            fd.append('nonce', meetbotCal.nonce);
            fd.append('page_url', pageUrl);

            fetch(meetbotCal.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success && res.data && res.data.slots) {
                        state.slotsByDate = {};
                        res.data.slots.forEach(function(s) {
                            var d = s.start.split('T')[0];
                            if (!state.slotsByDate[d]) state.slotsByDate[d] = [];
                            state.slotsByDate[d].push(s.start);
                        });
                    } else {
                        state.slotsByDate = {};
                    }
                    renderCalendar();
                })
                .catch(function() {
                    state.slotsByDate = {};
                    renderCalendar();
                });
        }

        function renderCalendar() {
            var grid = document.getElementById('meetbot-calendar-grid');
            var label = document.getElementById('meetbot-cal-month');

            label.textContent = months[state.calMonth] + ' ' + state.calYear;
            grid.innerHTML = '';

            dayHeaders.forEach(function(d) {
                var h = document.createElement('div');
                h.className = 'caldav-cal-header';
                h.textContent = d;
                grid.appendChild(h);
            });

            var first = new Date(state.calYear, state.calMonth, 1);
            var last = new Date(state.calYear, state.calMonth + 1, 0);
            var startDay = (first.getDay() + 6) % 7;

            var today = new Date();
            today.setHours(0,0,0,0);

            for (var i = 0; i < startDay; i++) {
                var empty = document.createElement('div');
                empty.className = 'caldav-cal-day';
                grid.appendChild(empty);
            }

            for (var d = 1; d <= last.getDate(); d++) {
                var date = new Date(state.calYear, state.calMonth, d);
                var cell = document.createElement('div');
                cell.className = 'caldav-cal-day';
                cell.textContent = d;

                var dateStr = fmtDate(date);

                if (date.getTime() === today.getTime()) {
                    cell.classList.add('today');
                }

                var hasSlots = state.slotsByDate && state.slotsByDate[dateStr] && state.slotsByDate[dateStr].length > 0;
                if (date >= today && hasSlots) {
                    cell.classList.add('available');
                    (function(ds, el) {
                        el.addEventListener('click', function() {
                            selectDate(ds, el);
                        });
                    })(dateStr, cell);
                }

                if (state.selectedDate === dateStr) {
                    cell.classList.add('selected');
                }

                grid.appendChild(cell);
            }
        }

        function selectDate(dateStr, cell) {
            state.selectedDate = dateStr;
            state.selectedSlot = null;

            document.querySelectorAll('.caldav-cal-day.selected').forEach(function(el) {
                el.classList.remove('selected');
            });
            cell.classList.add('selected');

            renderSlots(dateStr);
        }

        function renderSlots(dateStr) {
            var container = document.getElementById('meetbot-slots-container');
            var list = document.getElementById('meetbot-slots-list');
            var title = document.getElementById('meetbot-slots-title');

            container.classList.add('visible');

            var d = new Date(dateStr + 'T12:00:00');
            var weekdays = ['So','Mo','Di','Mi','Do','Fr','Sa'];
            var day = d.getDate();
            var mon = d.getMonth() + 1;
            title.textContent = weekdays[d.getDay()] + ' ' + (day < 10 ? '0' : '') + day + '.' + (mon < 10 ? '0' : '') + mon + '.';

            var slots = (state.slotsByDate && state.slotsByDate[dateStr]) || [];
            if (slots.length === 0) {
                list.innerHTML = '<div class="caldav-no-slots">Keine Termine frei.</div>';
                list.style.maxHeight = 'auto';
                return;
            }

            list.innerHTML = '';

            slots.forEach(function(t) {
                var dt = new Date(t);
                var hh = String(dt.getUTCHours()).padStart(2, '0');
                var mm = String(dt.getUTCMinutes()).padStart(2, '0');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'caldav-slot-btn';
                btn.textContent = hh + ':' + mm;
                btn.addEventListener('click', function() {
                    selectSlot(t, btn);
                });
                list.appendChild(btn);
            });

            list.style.maxHeight = (SLOTS_VISIBLE * 36) + 'px';
        }

        function selectSlot(isoTime, btn) {
            state.selectedSlot = isoTime;
            document.querySelectorAll('.caldav-slot-btn.selected').forEach(function(el) {
                el.classList.remove('selected');
            });
            btn.classList.add('selected');
            showSummary();
            goToStep(2);
        }

        function showSummary() {
            var el = document.getElementById('meetbot-summary');
            var d = new Date(state.selectedSlot);
            var hh = String(d.getUTCHours()).padStart(2, '0');
            var mm = String(d.getUTCMinutes()).padStart(2, '0');
            var dt = new Date(state.selectedDate + 'T12:00:00');
            var datum = dayNames[dt.getDay()] + ' ' + (dt.getDate() < 10 ? '0' : '') + dt.getDate() + '.' + (dt.getMonth()+1 < 10 ? '0' : '') + (dt.getMonth()+1) + '.' + dt.getFullYear();
            el.innerHTML = '<strong>' + datum + '</strong><br>' + hh + ':' + mm + ' Uhr (' + duration + ' min)';
        }

        function goToStep(n) {
            state.step = n;
            document.querySelectorAll('.caldav-step-content').forEach(function(el) {
                el.classList.toggle('active', el.id === 'meetbot-step-' + n);
            });
            document.querySelectorAll('.caldav-steps .caldav-step').forEach(function(el) {
                var s = parseInt(el.dataset.step, 10);
                el.classList.toggle('active', s === n);
                el.classList.toggle('completed', s < n);
            });
        }

        app.querySelectorAll('.caldav-back-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                goToStep(parseInt(btn.dataset.target, 10));
            });
        });

        document.getElementById('meetbot-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitBooking();
        });

        document.getElementById('meetbot-scroll-up').addEventListener('click', function() {
            var list = document.getElementById('meetbot-slots-list');
            list.scrollTop -= (36 * 3);
        });
        document.getElementById('meetbot-scroll-down').addEventListener('click', function() {
            var list = document.getElementById('meetbot-slots-list');
            list.scrollTop += (36 * 3);
        });

        function submitBooking() {
            var name  = document.getElementById('meetbot-name').value.trim();
            var email = document.getElementById('meetbot-email').value.trim();
            var notes = document.getElementById('meetbot-notes').value.trim();
            if (!name || !email) return;

            var btn = document.getElementById('meetbot-submit');
            btn.disabled = true;
            btn.textContent = '\u2026';

            var fd = new FormData();
            fd.append('action', 'meetbot_book');
            fd.append('nonce', meetbotCal.nonce);
            fd.append('page_url', pageUrl);
            fd.append('start', state.selectedSlot);
            fd.append('guest_name', name);
            fd.append('guest_email', email);
            fd.append('notes', notes);

            fetch(meetbotCal.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        var d = new Date(state.selectedSlot);
                        var hh = String(d.getUTCHours()).padStart(2, '0');
                        var mm = String(d.getUTCMinutes()).padStart(2, '0');
                        var dt = new Date(state.selectedDate + 'T12:00:00');
                        document.getElementById('meetbot-success-detail').textContent =
                            dayNames[dt.getDay()] + ' ' + dt.getDate() + '. ' + months[dt.getMonth()] + ' ' + dt.getFullYear() + ' \u2014 ' + hh + ':' + mm + ' Uhr';

                        // Show Google Meet link if available
                        var meetLink = res.data.meet_link || '';
                        var meetBox = document.getElementById('meetbot-meet-box');
                        var meetA   = document.getElementById('meetbot-meet-link');
                        if (meetLink && meetBox && meetA) {
                            meetA.href = meetLink;
                            meetA.textContent = meetLink;
                            meetBox.style.display = '';
                        } else if (meetBox) {
                            meetBox.style.display = 'none';
                        }

                        goToStep(3);
                    } else {
                        alert(res.data || 'Buchung fehlgeschlagen.');
                        btn.disabled = false;
                        btn.textContent = 'Termin best\u00e4tigen';
                    }
                })
                .catch(function() {
                    alert('Netzwerkfehler.');
                    btn.disabled = false;
                    btn.textContent = 'Termin best\u00e4tigen';
                });
        }

        function fmtDate(d) {
            return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
        }
    });
})();
