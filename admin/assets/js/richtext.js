(function () {
  'use strict';

  function addOption(sel, value, text) {
    var o = document.createElement('option');
    o.value = value;
    o.textContent = text;
    sel.appendChild(o);
  }

  function separator() {
    var s = document.createElement('span');
    s.className = 'rt-sep';
    return s;
  }

  function buildToolbar() {
    var bar = document.createElement('div');
    bar.className = 'rt-toolbar';
    bar.setAttribute('role', 'toolbar');

    var fonts = ['Arial', 'Verdana', 'Tahoma', 'Trebuchet MS', 'Times New Roman', 'Georgia', 'Courier New', 'Palatino Linotype'];
    var fontSel = document.createElement('select');
    fontSel.className = 'rt-select rt-font';
    fontSel.title = 'Estilo de letra';
    addOption(fontSel, '', 'Estilo de letra');
    fonts.forEach(function (f) { addOption(fontSel, f, f); });

    var sizeSel = document.createElement('select');
    sizeSel.className = 'rt-select rt-size';
    sizeSel.title = 'Tamaño de letra';
    addOption(sizeSel, '', 'Tamaño de letra');
    [
      [1, 'Muy pequeño'],
      [2, 'Pequeño'],
      [3, 'Normal'],
      [4, 'Mediano'],
      [5, 'Grande'],
      [6, 'Muy grande'],
      [7, 'Enorme']
    ].forEach(function (s) { addOption(sizeSel, String(s[0]), s[1]); });

    var color = document.createElement('input');
    color.type = 'color';
    color.className = 'rt-color';
    color.title = 'Color de texto';

    var commandBtn = function (title, cmd, value, label) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'rt-btn';
      b.title = title;
      b.innerHTML = label;
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });
      b.addEventListener('click', function () { runCommand(cmd, value); });
      return b;
    };

    bar.appendChild(fontSel);
    bar.appendChild(sizeSel);
    bar.appendChild(separator());
    bar.appendChild(commandBtn('Negrita', 'bold', null, 'B'));
    bar.appendChild(commandBtn('Cursiva', 'italic', null, '<i>I</i>'));
    bar.appendChild(commandBtn('Subrayado', 'underline', null, '<u>U</u>'));
    bar.appendChild(commandBtn('Tachado', 'strikeThrough', null, '<s>S</s>'));
    bar.appendChild(color);
    bar.appendChild(separator());
    bar.appendChild(commandBtn('Alinear a la izquierda', 'justifyLeft', null, 'Izq'));
    bar.appendChild(commandBtn('Centrar', 'justifyCenter', null, 'Cen'));
    bar.appendChild(commandBtn('Alinear a la derecha', 'justifyRight', null, 'Der'));
    bar.appendChild(commandBtn('Justificar', 'justifyFull', null, 'Just'));
    bar.appendChild(separator());
    bar.appendChild(commandBtn('Lista con viñetas', 'insertUnorderedList', null, '• Lista'));
    bar.appendChild(commandBtn('Lista numerada', 'insertOrderedList', null, '1. Lista'));
    bar.appendChild(commandBtn('Quitar formato', 'removeFormat', null, 'Limpiar formato'));

    return { bar: bar, fontSel: fontSel, sizeSel: sizeSel, color: color };
  }

  var current = null;

  document.addEventListener('selectionchange', function () {
    var el = document.activeElement;
    if (!el || !el.classList || !el.classList.contains('rt-editor')) return;
    var sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;
    current = { editor: el, range: sel.getRangeAt(0).cloneRange() };
  });

  function runCommand(cmd, value) {
    if (current && current.editor) {
      current.editor.focus();
      if (current.range) {
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(current.range);
      }
    }
    document.execCommand(cmd, false, value);
  }

  function init(ta) {
    var wrap = document.createElement('div');
    wrap.className = 'rt-wrap';

    var ui = buildToolbar();
    var ed = document.createElement('div');
    ed.className = 'rt-editor';
    ed.contentEditable = 'true';
    ed.spellcheck = true;

    var raw = ta.value;
    if (!/<[a-z][^>]*>/i.test(raw)) {
      var lines = raw.split(/\r?\n/).map(function (l) { return l.trim(); }).filter(function (l) { return l !== ''; });
      if (lines.length > 0) {
        raw = '<p>' + lines.join('</p><p>') + '</p>';
      }
    }
    ed.innerHTML = raw;

    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(ui.bar);
    wrap.appendChild(ed);
    ta.style.display = 'none';

    ui.fontSel.addEventListener('change', function () {
      if (ui.fontSel.value !== '') {
        runCommand('fontName', ui.fontSel.value);
        ui.fontSel.value = '';
      }
    });

    ui.sizeSel.addEventListener('change', function () {
      if (ui.sizeSel.value !== '') {
        runCommand('fontSize', ui.sizeSel.value);
        ui.sizeSel.value = '';
      }
    });

    ui.color.addEventListener('input', function () {
      runCommand('foreColor', ui.color.value);
    });

    ed.addEventListener('focus', function () {
      current = { editor: ed, range: null };
    });

    var form = ta.form;
    if (form) {
      form.addEventListener('submit', function () {
        var html = ed.innerHTML.trim().replace(/^<br\s*\/?>$/i, '');
        ta.value = html;
      });
    }
  }

  function boot() {
    var list = document.querySelectorAll('textarea[data-richtext]');
    for (var i = 0; i < list.length; i++) {
      init(list[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();