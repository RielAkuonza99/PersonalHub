<?php
$cats = []; foreach (q_all('SELECT id, label FROM skill_categories ORDER BY sort_order, id') as $c) $cats[$c['id']] = $c['label'];
layout_head('Skills', 'skills');
echo '<h2>Kategori</h2>';
crud(['table' => 'skill_categories', 'page' => 'skills', 'label' => 'kategori', 'order' => true,
  'fields' => ['key_name' => ['label' => 'Kunci (mis. Frontend)', 'max' => 60, 'required' => true], 'label' => ['label' => 'Label tampil', 'max' => 120, 'required' => true]],
  'list' => ['key_name' => 'Kunci', 'label' => 'Label']]);
echo '<h2>Skills</h2>';
if ($cats) crud(['table' => 'skills', 'page' => 'skills', 'label' => 'skill', 'soft' => true, 'order' => true, 'status' => true,
  'fields' => ['name' => ['label' => 'Nama skill', 'max' => 120, 'required' => true], 'category_id' => ['label' => 'Kategori', 'type' => 'select', 'options' => $cats], 'level' => ['label' => 'Level (0-100)', 'type' => 'number', 'min' => 0, 'max' => 100, 'required' => true]],
  'list' => ['name' => 'Skill', 'level' => 'Level']]);
else echo '<p class="muted">Buat kategori terlebih dahulu.</p>';
layout_foot();
