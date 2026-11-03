import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';

export function createEditor(element, content, onUpdate) {
    return new Editor({
        element,
        extensions: [StarterKit.configure({ link: false }), Image.configure({ allowBase64: false }), Link.configure({ openOnClick: false }), Subscript, Superscript],
        content: content || '',
        editorProps: { attributes: { class: 'prose prose-sm max-w-none min-h-72 px-4 py-3 focus:outline-none', role: 'textbox', 'aria-label': 'Konten artikel ilmiah', 'aria-multiline': 'true' } },
        onUpdate: ({ editor }) => onUpdate(editor.getHTML()),
    });
}
