import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

export default function Card({ card, onDelete }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging
    } = useSortable({
        id: `card-${card.id}`,
        data: { type: 'card', card }
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            className="bg-white p-3 rounded shadow cursor-grab active:cursor-grabbing"
        >
            <div className="flex justify-between">
                <span>{card.title}</span>
                <button
                    onClick={(e) => {
                        e.stopPropagation();
                        onDelete(card.id);
                    }}
                    className="text-red-500 hover:text-red-700 text-sm"
                >
                    ✕
                </button>
            </div>
            {card.description && (
                <p className="text-gray-500 text-sm mt-1">{card.description}</p>
            )}
        </div>
    );
}
