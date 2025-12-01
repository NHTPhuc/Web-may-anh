import { Chart } from "@/components/ui/chart"
/**
 * STROTE CAMERA - Camera Shop Admin JavaScript
 */

document.addEventListener("DOMContentLoaded", () => {
    // Khởi tạo các sự kiện
    initSidebar()
    initDataTables()
    initFormValidation()
    initImagePreview()
    initDeleteConfirmation()
    initRichTextEditor()
    initDatePicker()
    initCharts()
    initNotifications()
})

/**
 * Khởi tạo sidebar
 */
function initSidebar() {
    const toggleBtn = document.querySelector(".sidebar-toggle")
    const sidebar = document.querySelector(".sidebar")
    const content = document.querySelector(".content")

    if (toggleBtn && sidebar && content) {
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed")
            content.classList.toggle("expanded")
        })

        // Đóng sidebar trên mobile khi click vào content
        if (window.innerWidth < 768) {
            content.addEventListener("click", () => {
                sidebar.classList.add("collapsed")
                content.classList.add("expanded")
            })
        }
    }

    // Hiển thị submenu khi click vào menu có submenu
    const menuItems = document.querySelectorAll(".has-submenu")

    if (menuItems.length > 0) {
        menuItems.forEach((item) => {
            item.addEventListener("click", function(e) {
                e.preventDefault()
                this.classList.toggle("active")

                const submenu = this.querySelector(".submenu")
                if (submenu) {
                    if (submenu.style.maxHeight) {
                        submenu.style.maxHeight = null
                    } else {
                        submenu.style.maxHeight = submenu.scrollHeight + "px"
                    }
                }
            })
        })
    }
}

/**
 * Khởi tạo DataTables
 */
function initDataTables() {
    const tables = document.querySelectorAll(".datatable")

    if (tables.length > 0 && typeof $.fn.DataTable !== "undefined") {
        tables.forEach((table) => {
            $(table).DataTable({
                responsive: true,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json",
                },
            })
        })
    }
}

/**
 * Khởi tạo form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll(".needs-validation")

    if (forms.length > 0) {
        forms.forEach((form) => {
            form.addEventListener(
                "submit",
                function(e) {
                    if (!this.checkValidity()) {
                        e.preventDefault()
                        e.stopPropagation()
                    }

                    this.classList.add("was-validated")
                },
                false,
            )
        })
    }
}

/**
 * Khởi tạo xem trước hình ảnh
 */
function initImagePreview() {
    const imageInputs = document.querySelectorAll(".image-upload")

    if (imageInputs.length > 0) {
        imageInputs.forEach((input) => {
            input.addEventListener("change", function() {
                const preview = document.querySelector(this.dataset.preview)

                if (preview) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader()

                        reader.onload = (e) => {
                            preview.src = e.target.result
                            preview.style.display = "block"
                        }

                        reader.readAsDataURL(this.files[0])
                    }
                }
            })
        })
    }
}

/**
 * Khởi tạo xác nhận xóa
 */
function initDeleteConfirmation() {
    const deleteButtons = document.querySelectorAll(".btn-delete")

    if (deleteButtons.length > 0) {
        deleteButtons.forEach((button) => {
            button.addEventListener("click", function(e) {
                e.preventDefault()

                const confirmMessage = this.dataset.confirm || "Bạn có chắc chắn muốn xóa?"

                if (confirm(confirmMessage)) {
                    window.location.href = this.getAttribute("href")
                }
            })
        })
    }
}

/**
 * Khởi tạo trình soạn thảo văn bản
 */
function initRichTextEditor() {
    const editors = document.querySelectorAll(".rich-editor")

    if (editors.length > 0 && typeof ClassicEditor !== "undefined") {
        editors.forEach((editor) => {
            ClassicEditor.create(editor).catch((error) => {
                console.error(error)
            })
        })
    }
}

/**
 * Khởi tạo date picker
 */
function initDatePicker() {
    const datePickers = document.querySelectorAll(".date-picker")

    if (datePickers.length > 0 && typeof $.fn.datepicker !== "undefined") {
        datePickers.forEach((picker) => {
            $(picker).datepicker({
                format: "dd/mm/yyyy",
                autoclose: true,
                todayHighlight: true,
                language: "vi",
            })
        })
    }
}

/**
 * Khởi tạo biểu đồ
 */
function initCharts() {
    // Biểu đồ doanh thu
    const revenueChart = document.getElementById("revenue-chart")

    if (revenueChart && typeof Chart !== "undefined") {
        new Chart(revenueChart, {
            type: "line",
            data: {
                labels: ["T1", "T2", "T3", "T4", "T5", "T6", "T7", "T8", "T9", "T10", "T11", "T12"],
                datasets: [{
                    label: "Doanh thu",
                    data: [12, 19, 3, 5, 2, 3, 20, 33, 23, 12, 33, 55],
                    borderColor: "#3498db",
                    backgroundColor: "rgba(52, 152, 219, 0.1)",
                    borderWidth: 2,
                    fill: true,
                }, ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        })
    }

    // Biểu đồ sản phẩm bán chạy
    const productChart = document.getElementById("product-chart")

    if (productChart && typeof Chart !== "undefined") {
        new Chart(productChart, {
            type: "bar",
            data: {
                labels: ["Canon EOS 5D", "Nikon D850", "Sony A7 III", "Fujifilm X-T4", "Canon EF 24-70mm"],
                datasets: [{
                    label: "Số lượng bán",
                    data: [12, 19, 3, 5, 2],
                    backgroundColor: [
                        "rgba(52, 152, 219, 0.7)",
                        "rgba(46, 204, 113, 0.7)",
                        "rgba(155, 89, 182, 0.7)",
                        "rgba(241, 196, 15, 0.7)",
                        "rgba(231, 76, 60, 0.7)",
                    ],
                    borderColor: [
                        "rgba(52, 152, 219, 1)",
                        "rgba(46, 204, 113, 1)",
                        "rgba(155, 89, 182, 1)",
                        "rgba(241, 196, 15, 1)",
                        "rgba(231, 76, 60, 1)",
                    ],
                    borderWidth: 1,
                }, ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        })
    }

    // Biểu đồ danh mục
    const categoryChart = document.getElementById("category-chart")

    if (categoryChart && typeof Chart !== "undefined") {
        new Chart(categoryChart, {
            type: "doughnut",
            data: {
                labels: ["Máy ảnh DSLR", "Máy ảnh Mirrorless", "Ống kính", "Phụ kiện"],
                datasets: [{
                    data: [30, 40, 20, 10],
                    backgroundColor: [
                        "rgba(52, 152, 219, 0.7)",
                        "rgba(46, 204, 113, 0.7)",
                        "rgba(155, 89, 182, 0.7)",
                        "rgba(241, 196, 15, 0.7)",
                    ],
                    borderColor: [
                        "rgba(52, 152, 219, 1)",
                        "rgba(46, 204, 113, 1)",
                        "rgba(155, 89, 182, 1)",
                        "rgba(241, 196, 15, 1)",
                    ],
                    borderWidth: 1,
                }, ],
            },
            options: {
                responsive: true,
            },
        })
    }
}

/**
 * Khởi tạo thông báo
 */
function initNotifications() {
    // Tự động ẩn thông báo sau 5 giây
    const alerts = document.querySelectorAll(".alert")

    if (alerts.length > 0) {
        alerts.forEach((alert) => {
            setTimeout(() => {
                alert.style.opacity = "0"
                setTimeout(() => {
                    alert.remove()
                }, 500)
            }, 5000)
        })
    }
}

/**
 * Hiển thị thông báo
 */
function showNotification(message, type = "info") {
    // Tạo phần tử thông báo
    const notification = document.createElement("div")
    notification.className = `notification ${type}`
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `

    // Thêm vào body
    document.body.appendChild(notification)

    // Hiển thị thông báo
    setTimeout(() => {
        notification.classList.add("show")
    }, 10)

    // Thêm sự kiện đóng thông báo
    const closeButton = notification.querySelector(".notification-close")
    closeButton.addEventListener("click", () => {
        notification.classList.remove("show")
        setTimeout(() => {
            notification.remove()
        }, 300)
    })

    // Tự động ẩn thông báo sau 5 giây
    setTimeout(() => {
        notification.classList.remove("show")
        setTimeout(() => {
            notification.remove()
        }, 300)
    }, 5000)
}

/**
 * Cập nhật trạng thái đơn hàng
 */
function updateOrderStatus(orderId, status) {
    // Gửi request cập nhật trạng thái
    const formData = new FormData()
    formData.append("order_id", orderId)
    formData.append("status", status)

    fetch("ajax/update-order-status.php", {
            method: "POST",
            body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showNotification("Cập nhật trạng thái đơn hàng thành công!", "success")

                // Cập nhật UI
                const statusBadge = document.querySelector(`.order-status-${orderId}`)
                if (statusBadge) {
                    statusBadge.textContent = getStatusText(status)
                    statusBadge.className = `badge ${getStatusClass(status)} order-status-${orderId}`
                }
            } else {
                showNotification(data.message, "error")
            }
        })
        .catch((error) => {
            console.error("Error:", error)
            showNotification("Có lỗi xảy ra. Vui lòng thử lại sau.", "error")
        })
}

/**
 * Lấy text trạng thái đơn hàng
 */
function getStatusText(status) {
    switch (status) {
        case "pending":
            return "Chờ xử lý"
        case "processing":
            return "Đang xử lý"
        case "shipping":
            return "Đang giao hàng"
        case "completed":
            return "Hoàn thành"
        case "cancelled":
            return "Đã hủy"
        default:
            return "Không xác định"
    }
}

/**
 * Lấy class trạng thái đơn hàng
 */
function getStatusClass(status) {
    switch (status) {
        case "pending":
            return "badge-warning"
        case "processing":
            return "badge-info"
        case "shipping":
            return "badge-primary"
        case "completed":
            return "badge-success"
        case "cancelled":
            return "badge-danger"
        default:
            return "badge-secondary"
    }
}
/**
 * Thêm CSS cho thông báo
 */
;
(() => {
    const style = document.createElement("style")
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #777;
            margin-left: 10px;
        }
        
        .notification.success {
            border-left: 4px solid #2ecc71;
        }
        
        .notification.error {
            border-left: 4px solid #e74c3c;
        }
        
        .notification.info {
            border-left: 4px solid #3498db;
        }
        
        .notification.warning {
            border-left: 4px solid #f39c12;
        }
        
        @media (max-width: 768px) {
            .notification {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    `
    document.head.appendChild(style)
})()