/**
 * Handles file upload, validation, and integration with chat interface
 *
 * Provides:
 * - File selection and validation
 * - File preview/preview before sending
 * - Upload to /api/documents/upload endpoint on send
 * - Display of analysis results in chat
 * - Error handling and user feedback
 */

// Store selected file and form reference
let selectedFile = null;

$(document).ready(function () {
    const $fileInput = $("#doc-file-input");
    if ($fileInput.length === 0) return;

    $fileInput.on("change", function (e) {
        const file = e.target.files[0];
        if (!file) return;

        // (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert("File too large (max 10MB)");
            $(this).val("");
            return;
        }

        // Store file and show preview
        selectedFile = file;
        showFilePreview(file);
    });

    // Hook into submit button click to handle file upload
    // When there's a file, upload it instead of submitting the form
    $(document).on("click", '#ask-form button[type="submit"]', function (e) {
        if (selectedFile) {
            e.preventDefault();
            e.stopPropagation();
            uploadDocumentToChat(selectedFile);
            selectedFile = null;
            $("#doc-file-input").val("");
            clearFilePreview();
            return false;
        }
        // Otherwise, let the normal form submission proceed (iweb.form handles it)
    });
});

/**
 * Show file preview above the input form (not in chat history)
 * @param {File} file - The file to preview
 */
function showFilePreview(file) {
    const fileIcon = getFileIcon(file.name);
    const fileSize = (file.size / 1024).toFixed(2);

    $("#file-preview").remove();

    const previewHtml = `
        <div id="file-preview" class="document-preview-box">
            <div class="preview-inner">
                <span class="file-icon">${fileIcon}</span>
                <div class="file-info">
                    <div class="file-name">${escapeHtml(file.name)}</div>
                    <div class="file-size">${fileSize} KB</div>
                </div>
                <a href="javascript:clearFilePreview()" class="preview-close" title="Remove">×</a>
            </div>
        </div>
    `;

    const $form = $("#ask-form");
    $form.before(previewHtml);
}

function clearFilePreview() {
    $("#file-preview").remove();
    selectedFile = null;
    $("#doc-file-input").val("");
}

/**
 * Get appropriate icon for file type
 * @param {string} filename - The filename
 * @returns {string} Icon emoji
 */
function getFileIcon(filename) {
    const ext = filename.split(".").pop().toLowerCase();
    const iconMap = {
        pdf: "📄",
        doc: "📝",
        docx: "📝",
        jpg: "🖼️",
        jpeg: "🖼️",
        png: "🖼️",
        txt: "📃",
    };
    return iconMap[ext] || "📎";
}

function scrollToBottomWithRetry() {
    scrollChatToBottom();
    setTimeout(() => scrollChatToBottom(), 100);
    setTimeout(() => scrollChatToBottom(), 200);
}

function removeUploadingMessage(uploadingMessageId) {
    $(`#${uploadingMessageId}`).remove();
    $(`#${uploadingMessageId}`).next(".clearboth").remove();
}

function buildChatBubble(role, avatar, name, text, isHtml = false) {
    const escapedText = isHtml ? text : escapeHtml(text).replace(/\n/g, "<br>");
    const time = new Intl.DateTimeFormat(undefined, {
        timeStyle: "short",
    }).format(new Date());

    return `
        <div class="dialog ${role}">
          <div class="avatar">
            <div style="background-image:url('${avatar}')"></div>
          </div>
          <div class="name">${escapeHtml(name)}</div>
          <div class="time">${time}</div>
          <div class="clearboth"></div>
          <div class="txt">${escapedText}</div>
        </div>
        <div class="clearboth"></div>
    `;
}

/**
 * Extract analysis text from API response data, handles string and object formats
 *
 * @param {*} analysisData - Data from API response
 * @returns {string} Extracted analysis text
 */
function extractAnalysisText(analysisData) {
    if (!analysisData) {
        return "";
    }

    if (typeof analysisData === "string") {
        return analysisData;
    }

    if (typeof analysisData === "object" && analysisData.result) {
        return analysisData.result;
    }

    return "";
}

/**
 * Display AI response in chat and handle UI updates
 *
 * @param {HTMLElement} $chatArea - jQuery selector for chat message area
 * @param {string} message - Message to display
 * @param {boolean} clearPreview - Whether to clear file preview
 */
function showAIResponse($chatArea, message, clearPreview = true) {
    const responseHtml = buildChatBubble(
        "reply",
        "asset/image/logo-mmi.png",
        "AI-mmi",
        message,
        false
    );

    $chatArea.append(responseHtml);
    scrollToBottomWithRetry();

    if (clearPreview) {
        clearFilePreview();
    }
}

/**
 * Upload document to API and display results in chat
 * @param {File} file - The file to upload
 */
function uploadDocumentToChat(file) {
    const $askForm = $("#ask-form");
    const $showMessage = $(
        "main.page-body div.chat-area div.box > div.show-message"
    );

    if ($showMessage.length === 0) {
        alert("Chat area not found. Please refresh the page.");
        return;
    }

    $askForm.find("button").prop("disabled", true);

    const uploadingMessageId = "upload-status-" + Date.now();
    const uploadingHtml = `
        <div id="${uploadingMessageId}" class="dialog ask">
            <div class="avatar">
                <img src="asset/image/icon-member.png" alt="avatar">
            </div>
            <div class="name">You</div>
            <div class="clearboth"></div>
            <div class="txt">📤 Uploading document: <strong>${escapeHtml(
                file.name
            )}</strong></div>
        </div>
        <div class="clearboth"></div>
    `;

    $showMessage.append(uploadingHtml);
    scrollChatToBottom();

    // Prepare form data
    const formData = new FormData();
    formData.append("file", file);
    formData.append("analysis_type", "comprehensive");

    // Send to API
    $.ajax({
        url: "/api/documents/upload",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
        success: function (data) {
            $askForm.find("button").prop("disabled", false);
            removeUploadingMessage(uploadingMessageId);

            // Build and display the file upload message in chat
            const fileIcon = getFileIcon(file.name);
            const fileSize = (file.size / 1024).toFixed(2);
            const fileUploadHtml = `
                <div class="dialog ask">
                    <div class="avatar">
                        <img src="asset/image/icon-member.png" alt="avatar">
                    </div>
                    <div class="name">You</div>
                    <div class="time">${new Intl.DateTimeFormat(undefined, {
                        timeStyle: "short",
                    }).format(new Date())}</div>
                    <div class="clearboth"></div>
                    <div class="txt">
                        ${fileIcon} <strong>${escapeHtml(file.name)}</strong><br>
                        <small>${fileSize} KB</small>
                    </div>
                </div>
                <div class="clearboth"></div>
            `;

            // Append the file bubble to chat
            $showMessage.append(fileUploadHtml);
            scrollToBottomWithRetry();

            if (data.success && data.analysis) {
                const analysisText = extractAnalysisText(data.analysis);

                if (!analysisText || !analysisText.trim()) {
                    console.warn("No analysis text to display", data.analysis);
                    showAIResponse(
                        $showMessage,
                        "Could not extract analysis from document. Please try again.",
                        true
                    );
                    return;
                }

                showAIResponse($showMessage, analysisText, true);
            } else {
                const errorMessage =
                    data.message || "Unable to process this file. Please try uploading a PDF document.";
                showAIResponse($showMessage, errorMessage, true);
                console.error("Document upload failed", data);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $askForm.find("button").prop("disabled", false);
            removeUploadingMessage(uploadingMessageId);

            console.error("Upload error details:", {
                status: jqXHR.status,
                statusText: jqXHR.statusText,
                responseText: jqXHR.responseText,
                textStatus: textStatus,
                errorThrown: errorThrown,
            });

            // Try to parse JSON error response
            let errorMessage =
                "An error occurred while uploading. Please check your connection and try again.";
            try {
                const errorResponse = JSON.parse(jqXHR.responseText);
                if (errorResponse.message) {
                    errorMessage = errorResponse.message;
                }
            } catch (e) {
                // Not JSON, use default message
            }

            showAIResponse($showMessage, errorMessage, true);
        },
    });
}
