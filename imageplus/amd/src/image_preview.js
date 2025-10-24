// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Image preview modal functionality for ImagePlus plugin.
 *
 * @module     local_imageplus/image_preview
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize the image preview modal.
 */
export const init = () => {
    const modal = document.getElementById('local-imageplus-image-preview-modal');
    const modalImg = document.getElementById('local-imageplus-image-preview-img');
    const captionText = document.getElementById('local-imageplus-image-preview-caption');
    const closeBtn = document.querySelector('.local-imageplus-image-preview-close');

    if (!modal || !modalImg || !captionText || !closeBtn) {
        // Elements don't exist on this page, nothing to initialize
        return;
    }

    /**
     * Close and clear the modal.
     */
    const closeModal = () => {
        modal.style.display = 'none';
        modalImg.src = ''; // Clear the image
        captionText.innerHTML = '';
    };

    // Close modal when clicking X or outside image
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal || e.target === closeBtn) {
            closeModal();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });

    // Add click handlers to all file links
    document.addEventListener('click', (e) => {
        const target = e.target;
        if (target.classList.contains('local-imageplus-file-link') && target.tagName === 'A') {
            const href = target.getAttribute('href');
            const filename = target.textContent || target.innerText;

            // Check if it's an image file by checking both filename and href
            if (href && (/\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(filename) || /\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(href))) {
                e.preventDefault();
                e.stopPropagation();

                // Clear old image first
                modalImg.src = '';
                captionText.innerHTML = filename;

                // Show modal and load new image
                modal.style.display = 'block';
                modalImg.src = href;

                return false;
            }
        }
    });
};
