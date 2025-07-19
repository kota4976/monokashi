<?php
// セッションを開始し、ログインしていなければログインページにリダイレクト
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者画面</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Shippori+Mincho:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-title: 'Yusei Magic', cursive; /* タイトル用の可愛い手書き風フォント */
            --font-serif: 'Yu Gothic', sans-serif; /* セリフ体フォント */
            --font-body: 'M PLUS Rounded 1c', sans-serif; /* 全体用の丸ゴシック体フォント */
            --color-bg: #fdfaf6;
            --color-text: #3a2e28;
            --color-primary: #a07d5b;
            --color-card-bg: #ffffff;
            --color-danger: #d32f2f;
        }
        body {
            font-family: var(--font-body);
            background-color: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            padding: 15px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0 10px 20px 10px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0d9d1;
        }
        .page-header h1 {
            font-family: var(--font-title);
            margin: 0;
            font-size: 1.8rem;
            color: var(--color-primary);
        }
        .header-actions { display: flex; align-items: center; gap: 10px; }
        .nav-button, #genre-toggle-button, #search-toggle-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border: 1px solid var(--color-primary);
            color: var(--color-primary);
            background-color: transparent;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s;
        }
        .nav-button:hover, #genre-toggle-button:hover, #search-toggle-button:hover { background-color: var(--color-primary); color: white; }
        .nav-button svg, #genre-toggle-button svg, #search-toggle-button svg { width: 20px; height: 20px; fill: currentColor; }
        .nav-button-text { display: none; }
        
        .search-controls, .management-section {
            padding: 20px;
            background-color: var(--color-card-bg);
            border-radius: 8px;
            border: 1px solid #e0d9d1;
            margin: 20px 0;
            overflow: hidden;
            transition: all 0.4s ease-in-out;
            max-height: 1000px;
        }
        .search-controls.hidden, .management-section.hidden {
            max-height: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-top: 0;
            margin-bottom: 0;
            opacity: 0;
            border-width: 0;
        }
        .search-controls { display: flex; flex-direction: column; gap: 15px; }
        .search-controls > div { display: flex; flex-direction: column; }
        .search-controls label { margin-bottom: 5px; font-size: 14px; font-weight: bold; }
        .search-controls input, .search-controls select { font-size: 16px; padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
        
        .management-section h2 { font-family: var(--font-serif); text-align: center; }
        .form-inline { display: flex; justify-content: center; gap: 10px; }
        .form-inline input { font-size: 16px; padding: 10px; border-radius: 5px; border: 1px solid #ccc; flex-grow: 1; }
        .form-inline button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; background-color: #28a745; color: white; }
        #genre-list { list-style: none; padding: 0; margin-top: 20px; max-width: 400px; margin-left: auto; margin-right: auto; }
        #genre-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        #genre-list button { background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
        
        #imageList { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .photo-card { background: var(--color-card-bg); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; flex-direction: column; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; border-left: 5px solid var(--color-primary); }
        .photo-card:hover { transform: translateY(-5px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
        .photo-card img { width: 100%; height: 180px; object-fit: cover; border-top-left-radius: 3px; border-top-right-radius: 3px; }
        .photo-info { padding: 15px; }
        .photo-info p { margin: 0 0 10px 0; line-height: 1.6; }
        .photo-info p strong { margin-right: 8px; }
        .return-date { color: var(--color-danger); font-weight: bold; }
        
        /* ▼▼▼ モーダルのスタイルをlist.htmlと同じものに統一 ▼▼▼ */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: #fff; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; }
        .modal-content h2 { font-family: var(--font-serif); margin-top: 0; text-align: center; }
        .modal-body .input-group { margin-bottom: 20px; }
        .modal-body label { display: block; margin-bottom: 5px; font-weight: bold; }
        .modal-body select, .modal-body input { width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .modal-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .modal-footer button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px; }
        #save-button { background-color: #007bff; color: white; }
        #cancel-button { background-color: #6c757d; color: white; }
        #delete-button { background-color: #dc3545; color: white; margin-left: 0; }
        /* ▲▲▲ ここまで ▲▲▲ */

        @media (min-width: 768px) {
            body { padding: 40px; }
            .page-header h1 { font-size: 2.5rem; }
            .search-controls { flex-direction: row; justify-content: center; }
            .search-controls > div { flex: 1; max-width: 300px; }
            #imageList { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
            .nav-button, #genre-toggle-button, #search-toggle-button { padding: 8px 16px; }
            .nav-button-text { display: inline; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>管理者画面</h1>
            <div class="header-actions">
                <a href="upload.html" class="nav-button" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    <span class="nav-button-text">アップロード</span>
                </a>
                <button id="logout-button" class="nav-button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"></path></svg>
                    <span class="nav-button-text">ログアウト</span>
                </button>
                <button id="genre-toggle-button" aria-label="ジャンル管理を開く">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"></path></svg>
                    <span class="nav-button-text">ジャンル管理</span>
                </button>
                <button id="search-toggle-button" aria-label="検索フォームを開く">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                </button>
            </div>
        </header>
        
        <div class="search-controls hidden">
            <div>
                <label for="studentIdSearch">学籍番号で検索</label>
                <input type="text" id="studentIdSearch" placeholder="学籍番号を入力...">
            </div>
            <div>
                <label for="genreSearch">ジャンルで検索</label>
                <select id="genreSearch"></select>
            </div>
        </div>

        <div class="management-section hidden">
            <h2>ジャンル管理</h2>
            <form id="add-genre-form" class="form-inline">
                <input type="text" id="new-genre-name" placeholder="新しいジャンル名" required>
                <button type="submit">ジャンルを追加</button>
            </form>
            <ul id="genre-list"></ul>
        </div>

        <div id="imageList"><p>画像を読み込んでいます...</p></div>
    </div>

    <div id="edit-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>情報の編集</h2>
            <div class="modal-body">
                <input type="hidden" id="edit-image-name">
                <div class="input-group">
                    <label for="edit-genre">ジャンル</label>
                    <select id="edit-genre"></select>
                </div>
                <div class="input-group">
                    <label for="edit-return-date">返却予定日</label>
                    <input type="date" id="edit-return-date">
                </div>
            </div>
            <div class="modal-footer">
                <button id="delete-button">削除</button>
                <div>
                    <button id="cancel-button">キャンセル</button>
                    <button id="save-button">保存</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // JavaScriptに変更はありません
        const logoutButton = document.getElementById('logout-button');
        const genreToggleButton = document.getElementById('genre-toggle-button');
        const searchToggleButton = document.getElementById('search-toggle-button');
        const managementSection = document.querySelector('.management-section');
        const searchControls = document.querySelector('.search-controls');
        const addGenreForm = document.getElementById('add-genre-form');
        const newGenreNameInput = document.getElementById('new-genre-name');
        const genreListUl = document.getElementById('genre-list');
        const imageListDiv = document.getElementById('imageList');
        const studentIdSearch = document.getElementById('studentIdSearch');
        const genreSearch = document.getElementById('genreSearch');
        const modal = document.getElementById('edit-modal');
        const cancelButton = document.getElementById('cancel-button');
        const saveButton = document.getElementById('save-button');
        const deleteButton = document.getElementById('delete-button');
        const editImageNameInput = document.getElementById('edit-image-name');
        const editGenreSelect = document.getElementById('edit-genre');
        const editReturnDateInput = document.getElementById('edit-return-date');
        let allPhotos = [];
        async function loadGenres() { try { const response = await fetch('get_genres.php'); const genres = await response.json(); updateGenreUIs(genres); } catch (error) { console.error('ジャンルの読み込みに失敗:', error); } }
        function updateGenreUIs(genres) { genreSearch.innerHTML = ''; const allOption = document.createElement('option'); allOption.value = ''; allOption.textContent = 'すべてのジャンル'; genreSearch.appendChild(allOption); genres.forEach(genre => { const option = document.createElement('option'); option.value = genre; option.textContent = genre; genreSearch.appendChild(option); }); editGenreSelect.innerHTML = ''; genres.forEach(genre => { const option = document.createElement('option'); option.value = genre; option.textContent = genre; editGenreSelect.appendChild(option); }); genreListUl.innerHTML = ''; const protected_genres = ['小物(専門)', '小物(一般)', '未分類', 'その他']; genres.forEach(genre => { const li = document.createElement('li'); li.textContent = genre; if (!protected_genres.includes(genre)) { const deleteBtn = document.createElement('button'); deleteBtn.textContent = '削除'; deleteBtn.onclick = () => deleteGenre(genre); li.appendChild(deleteBtn); } genreListUl.appendChild(li); }); }
        async function deleteGenre(genreName) { if (!confirm(`ジャンル「${genreName}」を削除しますか？\nこのジャンルが設定されている画像がある場合は削除できません。`)) return; try { const response = await fetch('delete_genre.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ genre_name: genreName }) }); const result = await response.json(); alert(result.message); if (result.success) loadGenres(); } catch (error) { alert('通信エラーが発生しました。'); } }
        async function fetchAndRenderPhotos() { try { const response = await fetch('get_photos.php'); if (!response.ok) throw new Error('サーバーからの応答がありません。'); allPhotos = await response.json(); applyFiltersAndRender(); } catch (error) { imageListDiv.innerHTML = `<p>画像の読み込みに失敗しました: ${error.message}</p>`; } }
        function applyFiltersAndRender() { const studentId = studentIdSearch.value.trim().toLowerCase(); const genre = genreSearch.value; let filteredPhotos = [...allPhotos]; if (studentId) filteredPhotos = filteredPhotos.filter(p => p.student_id && p.student_id.toLowerCase().includes(studentId)); if (genre) filteredPhotos = filteredPhotos.filter(p => p.genre === genre); renderPhotoCards(filteredPhotos); }
        function renderPhotoCards(photos) { imageListDiv.innerHTML = ''; if (photos.length === 0) { imageListDiv.innerHTML = '<p>画像はありません。</p>'; return; } photos.forEach(photo => { const card = document.createElement('div'); card.className = 'photo-card'; card.dataset.imageName = photo.image_name; card.innerHTML = `<img src="${photo.imageUrl}" alt="${photo.student_id}"><div class="photo-info"><p><strong>学籍番号:</strong> ${photo.student_id}</p><p><strong>ジャンル:</strong> ${photo.genre || 'なし'}</p>${photo.return_date ? `<p class="return-date"><strong>返却予定:</strong> ${photo.return_date}</p>` : ''}</div>`; card.addEventListener('click', () => openEditModal(photo)); imageListDiv.appendChild(card); }); }
        function openEditModal(photo) { editImageNameInput.value = photo.image_name; editReturnDateInput.value = photo.return_date || ''; editGenreSelect.value = photo.genre || ''; modal.style.display = 'flex'; }
        function closeEditModal() { modal.style.display = 'none'; }
        async function deletePhoto() { const imageName = editImageNameInput.value; if (!imageName || !confirm('本当にこの画像と情報を完全に削除しますか？\nこの操作は元に戻せません。')) return; try { const response = await fetch('delete_photo.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ image_name: imageName }) }); const result = await response.json(); if (result.success) { alert('削除しました。'); closeEditModal(); fetchAndRenderPhotos(); } else { alert('削除に失敗しました: ' + result.message); } } catch (error) { alert('通信エラーが発生しました。'); } }
        async function saveChanges() { const imageName = editImageNameInput.value; const selectedGenre = editGenreSelect.value; const returnDate = editReturnDateInput.value; try { const response = await fetch('update_photo.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ image_name: imageName, genre: selectedGenre, return_date: returnDate }) }); const result = await response.json(); if (result.success) { alert('更新しました。'); closeEditModal(); fetchAndRenderPhotos(); } else { alert('更新に失敗しました: ' + result.message); } } catch (error) { alert('通信エラーが発生しました。'); } }
        window.addEventListener('load', () => { loadGenres(); fetchAndRenderPhotos(); });
        addGenreForm.addEventListener('submit', async (e) => { e.preventDefault(); const newGenre = newGenreNameInput.value.trim(); if (!newGenre) return; try { const response = await fetch('add_genre.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ genre_name: newGenre }) }); const result = await response.json(); alert(result.message); if (result.success) { newGenreNameInput.value = ''; loadGenres(); } } catch (error) { alert('通信エラーが発生しました。'); } });
        logoutButton.addEventListener('click', () => { if (confirm('本当にログアウトしますか？')) window.location.href = 'logout.php'; });
        genreToggleButton.addEventListener('click', () => managementSection.classList.toggle('hidden'));
        searchToggleButton.addEventListener('click', () => searchControls.classList.toggle('hidden'));
        studentIdSearch.addEventListener('input', applyFiltersAndRender);
        genreSearch.addEventListener('change', applyFiltersAndRender);
        cancelButton.addEventListener('click', closeEditModal);
        saveButton.addEventListener('click', saveChanges);
        deleteButton.addEventListener('click', deletePhoto);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeEditModal(); });
    </script>
</body>
</html>