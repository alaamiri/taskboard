export default function CardForm({ form, onSubmit, onCancel }) {
    return (
        <form onSubmit={onSubmit}>
            <input
                type="text"
                value={form.data.title}
                onChange={(e) => form.setData('title', e.target.value)}
                placeholder="Titre de la carte"
                className="w-full border rounded px-2 py-1 mb-2 text-sm"
                autoFocus
            />
            <div className="flex gap-2">
                <button
                    type="submit"
                    className="bg-blue-500 text-white px-2 py-1 rounded text-sm"
                >
                    Ajouter
                </button>
                <button
                    type="button"
                    onClick={onCancel}
                    className="px-2 py-1 text-sm"
                >
                    Annuler
                </button>
            </div>
        </form>
    );
}
