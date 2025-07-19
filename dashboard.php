<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ダッシュボード</title>
    <style>
        /* ... （前回のスタイルと同じなので省略） ... */
        body { font-family: sans-serif; background-color: #f4f4f9; margin: 0; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        h1, h2 { text-align: center; }
        .management-section { padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-inline { display: flex; justify-content: center; gap: 10px; }
        .form-inline input { font-size: 16px; padding: 10px; border-radius: 5px; border: 1px solid #ccc; flex-grow: 1; }
        .form-inline button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; background-color: #28a745; color: white; }
        
        /* ▼▼▼ ジャンルリストのスタイルを追加 ▼▼▼ */
        #genre-list { list-style: none; padding: 0; margin-top: 20px; }
        #genre-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        #genre-list button { background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
        /* ▲▲▲ ここまで ▲▲▲ */

        #imageList { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }
        .photo-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow: hidden; width: 280px; cursor: pointer; transition: transform 0.2s; }
        .photo-card:hover { transform: translateY(-5px); }
        .photo-card img { width: 100%; height: 200px; object-fit: cover; display: block; background-color: #eee; }
        .photo-info { padding: 15px; }
        .photo-info p { margin: 0 0 8px 0; word-wrap: break-word; }
        .return-date { color: #d32f2f; font-weight: bold; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: #fff; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; }
        .modal-body .input-group { margin-bottom: 20px; }
        .modal-body label { display: block; margin-bottom: 5px; font-weight: bold; }
        .modal-body select, .modal-body input { width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .modal-footer { display: flex; justify-content: space-between; margin-top: 20px; }
        .modal-footer button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        #save-button { background-color: #007bff; color: white; }
        #cancel-button { background-color: #6c757d; color: white; }
        #delete-button { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>管理者ダッシュボード</h1>

        <div class="management-section">
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
        const addGenreForm = document.getElementById('add-genre-form');
        const newGenreNameInput = document.getElementById('new-genre-name');
        const genreListUl = document.getElementById('genre-list'); // ジャンルリストのUL要素

        async function loadGenres() {
            try {
                const response = await fetch('get_genres.php');
                const genres = await response.json();

                // 編集モーダルのセレクトボックスを更新
                const editGenreSelect = document.getElementById('edit-genre');
                editGenreSelect.innerHTML = '';
                genres.forEach(genre => {
                    const option = document.createElement('option');
                    option.value = genre;
                    option.textContent = genre;
                    editGenreSelect.appendChild(option);
                });

                // ▼▼▼ ジャンル管理の一覧リストを更新 ▼▼▼
                genreListUl.innerHTML = '';
                const protected_genres = ['小物(専門)', '小物(一般)', '未分類', 'その他'];
                genres.forEach(genre => {
                    const li = document.createElement('li');
                    li.textContent = genre;

                    // 基本ジャンル以外に削除ボタンを追加
                    if (!protected_genres.includes(genre)) {
                        const deleteBtn = document.createElement('button');
                        deleteBtn.textContent = '削除';
                        deleteBtn.onclick = () => deleteGenre(genre);
                        li.appendChild(deleteBtn);
                    }
                    genreListUl.appendChild(li);
                });
                // ▲▲▲ ここまで ▲▲▲

            } catch (error) {
                console.error('ジャンルの読み込みに失敗:', error);
            }
        }

        // ▼▼▼ ジャンル削除の関数を追加 ▼▼▼
        async function deleteGenre(genreName) {
            if (!confirm(`ジャンル「${genreName}」を削除しますか？\nこのジャンルが設定されている画像がある場合は削除できません。`)) {
                return;
            }
            try {
                const response = await fetch('delete_genre.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ genre_name: genreName })
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    loadGenres(); // 成功したらリストを再読み込み
                }
            } catch (error) {
                alert('通信エラーが発生しました。');
            }
        }
        // ▲▲▲ ここまで ▲▲▲

        addGenreForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const newGenre = newGenreNameInput.value.trim();
            if (!newGenre) return;
            try {
                const response = await fetch('add_genre.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ genre_name: newGenre })
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    newGenreNameInput.value = '';
                    loadGenres();
                }
            } catch (error) {
                alert('通信エラーが発生しました。');
            }
        });

        // ... (以降のJavaScriptは変更なし) ...
        const imageListDiv = document.getElementById('imageList');
        const modal = document.getElementById('edit-modal');
        const cancelButton = document.getElementById('cancel-button');
        const saveButton = document.getElementById('save-button');
        const deleteButton = document.getElementById('delete-button');
        const editImageNameInput = document.getElementById('edit-image-name');
        const editReturnDateInput = document.getElementById('edit-return-date');
        let allPhotos = [];
        window.addEventListener('load', () => {
            loadGenres();
            fetchAndRenderPhotos();
        });
        async function fetchAndRenderPhotos() {
            try {
                const response = await fetch('get_photos.php');
                if (!response.ok) throw new Error('サーバーからの応答がありません。');
                allPhotos = await response.json();
                renderPhotoCards(allPhotos);
            } catch (error) {
                imageListDiv.innerHTML = `<p>画像の読み込みに失敗しました: ${error.message}</p>`;
            }
        }
        function renderPhotoCards(photos) {
            imageListDiv.innerHTML = '';
            if (photos.length === 0) {
                imageListDiv.innerHTML = '<p>画像はありません。</p>';
                return;
            }
            photos.forEach(photo => {
                const card = document.createElement('div');
                card.className = 'photo-card';
                card.dataset.imageName = photo.image_name;
                card.innerHTML = `
                    <img src="${photo.imageUrl}" alt="${photo.student_id}">
                    <div class="photo-info">
                        <p><strong>学籍番号:</strong> ${photo.student_id}</p>
                        <p><strong>ジャンル:</strong> ${photo.genre || 'なし'}</p>
                        ${photo.return_date ? `<p class="return-date"><strong>返却予定:</strong> ${photo.return_date}</p>` : ''}
                    </div>
                `;
                card.addEventListener('click', () => openEditModal(photo));
                imageListDiv.appendChild(card);
            });
        }
        function openEditModal(photo) {
            editImageNameInput.value = photo.image_name;
            editReturnDateInput.value = photo.return_date || '';
            document.getElementById('edit-genre').value = photo.genre || '';
            modal.style.display = 'flex';
        }
        function closeEditModal() { modal.style.display = 'none'; }
        async function deletePhoto() {
            const imageName = editImageNameInput.value;
            if (!imageName) { alert('対象の画像が選択されていません。'); return; }
            if (!confirm('本当にこの画像と情報を完全に削除しますか？\nこの操作は元に戻せません。')) { return; }
            try {
                const response = await fetch('delete_photo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_name: imageName })
                });
                const result = await response.json();
                if (result.success) {
                    alert('削除しました。');
                    closeEditModal();
                    fetchAndRenderPhotos();
                } else {
                    alert('削除に失敗しました: ' + result.message);
                }
            } catch (error) {
                alert('通信エラーが発生しました。');
            }
        }
        async function saveChanges() {
            const imageName = editImageNameInput.value;
            const selectedGenre = document.getElementById('edit-genre').value;
            const returnDate = editReturnDateInput.value;
            try {
                const response = await fetch('update_photo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        image_name: imageName,
                        genre: selectedGenre,
                        return_date: returnDate
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert('更新しました。');
                    closeEditModal();
                    fetchAndRenderPhotos();
                } else {
                    alert('更新に失敗しました: ' + result.message);
                }
            } catch (error) {
                alert('通信エラーが発生しました。');
            }
        }
        cancelButton.addEventListener('click', closeEditModal);
        saveButton.addEventListener('click', saveChanges);
        deleteButton.addEventListener('click', deletePhoto);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeEditModal(); });
    </script>
</body>
</html>