import {
    closestCenter,
    DndContext,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    type DragEndEvent,
    type DraggableAttributes,
    type DraggableSyntheticListeners,
} from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { ReactNode } from 'react';

export interface DragHandleProps {
    attributes: DraggableAttributes;
    listeners: DraggableSyntheticListeners;
    isDragging: boolean;
}

/**
 * Vertical list reorderable by mouse, touch and keyboard (space to lift, arrows to move).
 */
export function SortableList<T>({
    items,
    getId,
    onReorder,
    children,
}: {
    items: T[];
    getId: (item: T) => string;
    onReorder: (items: T[]) => void;
    children: (item: T, index: number, handle: DragHandleProps) => ReactNode;
}) {
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const ids = items.map(getId);

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) {
            return;
        }

        onReorder(arrayMove(items, ids.indexOf(String(active.id)), ids.indexOf(String(over.id))));
    };

    return (
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
            <SortableContext items={ids} strategy={verticalListSortingStrategy}>
                {items.map((item, index) => (
                    <SortableItem key={ids[index]} id={ids[index]}>
                        {(handle) => children(item, index, handle)}
                    </SortableItem>
                ))}
            </SortableContext>
        </DndContext>
    );
}

function SortableItem({ id, children }: { id: string; children: (handle: DragHandleProps) => ReactNode }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Translate.toString(transform), transition, zIndex: isDragging ? 10 : undefined, position: 'relative' }}
        >
            {children({ attributes, listeners, isDragging })}
        </div>
    );
}
