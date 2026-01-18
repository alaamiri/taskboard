import { useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function useBoardChannel(boardId) {
    useEffect(() => {
        if (!window.Echo) {
            console.error('Echo non initialisé');
            return;
        }

        const channel = window.Echo.join(`board.${boardId}`)
            .here((users) => {
                console.log('Utilisateurs connectés:', users);
            })
            .joining((user) => {
                console.log('Utilisateur rejoint:', user.name);
            })
            .leaving((user) => {
                console.log('Utilisateur parti:', user.name);
            })
            .listen('.card.moved', () => {
                router.reload({ only: ['board'] });
            })
            .listen('.card.deleted', () => {
                router.reload({ only: ['board'] });
            })
            .listen('.column.deleted', () => {
                router.reload({ only: ['board'] });
            })
            .listen('.board.updated', () => {
                router.reload({ only: ['board'] });
            })
            .error((error) => {
                console.error('Erreur canal:', error);
            });

        return () => {
            window.Echo.leave(`board.${boardId}`);
        };
    }, [boardId]);
}
