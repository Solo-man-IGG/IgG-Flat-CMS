    </div>

    <div id="fileBrowserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; border-radius: 0.5rem; max-width: 800px; margin: 60px auto; max-height: 85vh; display: flex; flex-direction: column;">
            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">選擇圖片</h3>
                <button type="button" onclick="closeFileBrowser()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; padding: 0.25rem;">&times;</button>
            </div>
            <div id="fileBrowserContent" style="flex: 1; overflow-y: auto; padding: 1rem;">
                <p style="text-align: center; color: #94a3b8;">載入中...</p>
            </div>
            <div style="padding: 0.75rem 1.5rem; border-top: 1px solid #e2e8f0; text-align: right;">
                <a href="/admin/files" target="_blank" class="btn" style="font-size: 0.8rem; text-decoration: none;">前往檔案管理</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        var activeEasyMDE = null;

        var easyMdeToolbar = [
            'bold', 'italic', 'heading', '|',
            'quote', 'unordered-list', 'ordered-list', '|',
            'link',
            {
                name: 'insertImage',
                action: function openFileBrowser(editor) {
                    activeEasyMDE = editor;
                    document.getElementById('fileBrowserModal').style.display = 'block';
                    loadFileBrowser();
                },
                className: 'fa fa-image',
                title: '插入圖片',
            },
            '|',
            'table', '|',
            'preview', 'side-by-side', 'fullscreen', '|',
            'guide'
        ];

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('textarea[data-easymde]').forEach(function(el) {
                new EasyMDE({
                    element: el,
                    forceSync: true,
                    autofocus: false,
                    spellChecker: false,
                    toolbar: easyMdeToolbar,
                    renderingConfig: {
                        singleLineBreaks: true,
                        codeSyntaxHighlighting: true
                    }
                });
            });
        });

        function loadFileBrowser() {
            var container = document.getElementById('fileBrowserContent');
            container.innerHTML = '<p style="text-align: center; color: #94a3b8;">載入中...</p>';

            fetch('/admin/files?action=list')
                .then(function(r) { return r.json(); })
                .then(function(files) {
                    if (!files || files.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: #94a3b8;">尚無圖片，請先上傳。</p>';
                        return;
                    }

                    function escapeHtml(str) {
                        var div = document.createElement('div');
                        div.appendChild(document.createTextNode(str));
                        return div.innerHTML;
                    }
                    var html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem;">';
                    files.forEach(function(f) {
                        var safeUrl = escapeHtml(f.url);
                        var safeName = escapeHtml(f.name);
                        html += '<div class="fb-item" data-url="' + safeUrl + '" onclick="insertFileImage(this)" style="cursor: pointer; border: 1px solid #e2e8f0; border-radius: 0.375rem; overflow: hidden; transition: box-shadow 0.2s; background: #f8fafc;" onmouseover="this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.15)\'" onmouseout="this.style.boxShadow=\'none\'">';
                        html += '<div style="height: 100px; display: flex; align-items: center; justify-content: center; background: #fff; overflow: hidden;"><img src="' + safeUrl + '" alt="' + safeName + '" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>';
                        html += '<div style="padding: 0.4rem 0.5rem; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' + safeName + '">' + safeName + '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                    container.innerHTML = html;
                })
                .catch(function() {
                    container.innerHTML = '<p style="text-align: center; color: #ef4444;">載入失敗。</p>';
                });
        }

        function insertFileImage(el) {
            var url = el.getAttribute('data-url');
            if (activeEasyMDE) {
                activeEasyMDE.codemirror.replaceSelection('![](' + url + ')');
            }
            closeFileBrowser();
        }

        function closeFileBrowser() {
            document.getElementById('fileBrowserModal').style.display = 'none';
            activeEasyMDE = null;
        }

        document.getElementById('fileBrowserModal').addEventListener('click', function(e) {
            if (e.target === this) closeFileBrowser();
        });
    </script>
</body>
</html>
